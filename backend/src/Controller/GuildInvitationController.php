<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\GameGuild;
use App\Entity\GuildInvitation;
use App\Entity\User;
use App\Enum\GuildRole;
use App\Repository\GameGuildRepository;
use App\Repository\GuildInvitationRepository;
use App\Repository\GuildMembershipRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class GuildInvitationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GuildInvitationRepository $invitationRepository,
        private readonly GameGuildRepository $guildRepository,
        private readonly UserRepository $userRepository,
        private readonly GuildMembershipRepository $membershipRepository,
    ) {}

    #[Route('/api/guilds/{id}/invitations', name: 'api_guild_invitation_generate', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function generateInvitation(string $id, Request $request): JsonResponse
    {
        $guild = $this->guildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $guild);

        $securityUser = $this->getUser();
        if (!$securityUser) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['discordId' => $securityUser->getDiscordId()]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            $payload = [];
        }

        $roleString = $payload['role'] ?? 'Member';
        $role = GuildRole::tryFrom($roleString) ?? GuildRole::MEMBER;

        $expiresAt = null;
        if (isset($payload['expiresInDays']) && is_numeric($payload['expiresInDays'])) {
            $days = max(1, min(30, (int)$payload['expiresInDays']));
            $expiresAt = (new \DateTimeImmutable())->modify("+{$days} days");
        } else {
            $expiresAt = (new \DateTimeImmutable())->modify('+7 days');
        }

        try {
            $invitation = new GuildInvitation($guild, $user, $role, $expiresAt);
            $this->em->persist($invitation);
            $this->em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to create invitation', 'hint' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'id' => $invitation->getUuidToString(),
            'token' => $invitation->getToken(),
            'role' => $invitation->getRole()->value,
            'expiresAt' => $invitation->getExpiresAt()?->format('c'),
            'maxUses' => $invitation->getMaxUses(),
            'usedCount' => $invitation->getUsedCount(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/invitations/{token}/accept', name: 'api_invitation_accept', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function acceptInvitation(string $token): JsonResponse
    {
        $invitation = $this->invitationRepository->findOneBy(['token' => $token]);
        if (!$invitation) {
            return $this->json(['error' => 'Invitation not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$invitation->canBeUsed()) {
            $reason = 'Invitation cannot be used';
            if (!$invitation->isActive()) {
                $reason = 'Invitation has been revoked';
            } elseif ($invitation->isExpired()) {
                $reason = 'Invitation has expired';
            } elseif ($invitation->getUsedCount() >= $invitation->getMaxUses()) {
                $reason = 'Invitation has already been used';
            }
            return $this->json(['error' => $reason], Response::HTTP_FORBIDDEN);
        }

        $securityUser = $this->getUser();
        if (!$securityUser) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['discordId' => $securityUser->getDiscordId()]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $existingMembership = $this->membershipRepository->findOneBy([
            'user' => $user,
            'guild' => $invitation->getGuild(),
        ]);

        if ($existingMembership) {
            return $this->json(['error' => 'You are already a member of this guild'], Response::HTTP_CONFLICT);
        }

        try {
            $membership = new \App\Entity\GuildMembership(
                $user,
                $invitation->getGuild(),
                $invitation->getRole()
            );

            $this->em->persist($membership);

            $invitation->markAsUsed($user);
            $this->em->persist($invitation);

            $this->em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to join guild', 'hint' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'message' => 'Successfully joined guild',
            'guild' => [
                'id' => $invitation->getGuild()->getUuidToString(),
                'name' => $invitation->getGuild()->getName(),
            ],
            'role' => $invitation->getRole()->value,
        ], Response::HTTP_OK);
    }

    #[Route('/api/guilds/{id}/invitations', name: 'api_guild_invitation_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function listInvitations(string $id): JsonResponse
    {
        $guild = $this->guildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $guild);

        $invitations = $this->invitationRepository->findBy(
            ['guild' => $guild],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function (GuildInvitation $inv) {
            return [
                'id' => $inv->getUuidToString(),
                'token' => $inv->getToken(),
                'role' => $inv->getRole()->value,
                'createdAt' => $inv->getCreatedAt()->format('c'),
                'expiresAt' => $inv->getExpiresAt()?->format('c'),
                'createdBy' => [
                    'id' => $inv->getCreatedBy()->getUuidToString(),
                    'username' => $inv->getCreatedBy()->getUsername(),
                ],
                'isActive' => $inv->isActive(),
                'isExpired' => $inv->isExpired(),
                'usedCount' => $inv->getUsedCount(),
                'maxUses' => $inv->getMaxUses(),
                'remainingUses' => $inv->getRemainingUses(),
                'usedBy' => $inv->getUsedBy() ? [
                    'id' => $inv->getUsedBy()->getUuidToString(),
                    'username' => $inv->getUsedBy()->getUsername(),
                ] : null,
                'usedAt' => $inv->getUsedAt()?->format('c'),
            ];
        }, $invitations);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/api/invitations/{id}', name: 'api_invitation_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteInvitation(string $id): JsonResponse
    {
        $invitation = $this->invitationRepository->find($id);
        if (!$invitation) {
            return $this->json(['error' => 'Invitation not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $invitation->getGuild());

        try {
            $invitation->revoke();
            $this->em->persist($invitation);
            $this->em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to revoke invitation', 'hint' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['message' => 'Invitation revoked successfully'], Response::HTTP_OK);
    }
}
