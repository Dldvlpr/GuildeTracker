<?php

namespace App\Controller;

use App\DTO\GuildMembershipDTO;
use App\Entity\GameGuild;
use App\Repository\GameCharacterRepository;
use App\Repository\GameGuildRepository;
use App\Repository\GuildMembershipRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Enum\GuildRole;
use Doctrine\ORM\EntityManagerInterface;

class GuildMembershipController extends AbstractController
{
    public function __construct(
        private readonly GuildMembershipRepository $guildMembershipRepository,
        private readonly GameGuildRepository $gameGuildRepository,
        private readonly userRepository $userRepository,
        private readonly GameCharacterRepository $gameCharacterRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {}

    #[Route('/api/guilds/{id}/members', name: 'api_guild_members_list', methods: ['GET'])]
    public function listGuildMembers(string $id): JsonResponse
    {
        $guild = $this->gameGuildRepository->findOneBy(['id' => $id]);
        if (!($guild instanceof GameGuild))
            return $this->json(['error' => 'Guild not found'], 404);

        $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);
        $this->denyAccessUnlessGranted('GUILD_USE_FEATURES', $guild);

        $guildMembers = $this->guildMembershipRepository->findBy(['guild' => $guild]);

        return $this->json(GuildMembershipDTO::fromEntities($guildMembers));
    }

    #[Route('/api/members/{id}', name: 'api_member_update', methods: ['PATCH'])]
    public function updateMember(string $id, Request $request): JsonResponse
    {
        $guildMember = $this->guildMembershipRepository->findOneBy(['id' => $id]);

        if (!$guildMember) {
            return $this->json(['message' => 'Member not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $guildMember->getGuild());
        $this->denyAccessUnlessGranted('GUILD_USE_FEATURES', $guildMember->getGuild());

        $data = json_decode($request->getContent(), true);

        if (!isset($data['role'])) {
            return $this->json(['message' => 'Role is required'], 400);
        }

        $roleString = $data['role'];

        $role = GuildRole::tryFrom($roleString);
        if (!$role) {
            return $this->json(['message' => 'Invalid role. Accepted values: Member, Officer, GM'], 400);
        }

        $guildMember->setRole($role);
        $this->entityManager->flush();

        return $this->json(GuildMembershipDTO::fromEntity($guildMember));
    }

    #[Route('/api/members/{id}', name: 'api_member_delete', methods: ['DELETE'])]
    public function deleteMember(string $id, Request $request): JsonResponse
    {
        $guildMember = $this->guildMembershipRepository->findOneBy(['id' => $id]);
        if (!$guildMember) {
            return $this->json(['message' => 'No member found!'], 400);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $guildMember->getGuild());
        $this->denyAccessUnlessGranted('GUILD_USE_FEATURES', $guildMember->getGuild());

        $user = $guildMember->getUser();
        $guild = $guildMember->getGuild();

        $characters = $this->gameCharacterRepository->findBy([
            'userPlayer' => $user,
            'guild' => $guild
        ]);

        foreach ($characters as $character) {
            $this->entityManager->remove($character);
        }

        $this->entityManager->remove($guildMember);
        $this->entityManager->flush();

        return $this->json('ok', 204);
    }
}
