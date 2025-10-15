<?php

namespace App\Controller;

use App\DTO\GuildMembershipDTO;
use App\Entity\GameGuild;
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
        private readonly EntityManagerInterface $entityManager,
    )
    {}

    #[Route('/api/guildmembers/{id}', name: 'api_guildmembers', methods: ['GET'])]
    public function getGuildMembership(string $id): JsonResponse
    {
        $guild = $this->gameGuildRepository->findOneBy(['id' => $id]);
        if (!($guild instanceof GameGuild))
            return $this->json(['error' => 'Guild not found'], 404);

        // $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);


        $guildMembers = $this->guildMembershipRepository->findBy(['guild' => $guild]);

        return $this->json(GuildMembershipDTO::fromEntities($guildMembers));
    }

    #[Route('/api/guildmembers/{id}', name: 'api_guildmembers_update', methods: ['PATCH'])]
    public function editGuildMembership(string $id, Request $request): JsonResponse
    {
        $guildMember = $this->guildMembershipRepository->findOneBy(['id' => $id]);

        if (!$guildMember) {
            return $this->json(['message' => 'Membre non trouvé'], 404);
        }

        // $this->denyAccessUnlessGranted('GUILD_MANAGE', $guildMember->getGuild());

        $data = json_decode($request->getContent(), true);

        if (!isset($data['role'])) {
            return $this->json(['message' => 'Le rôle est requis'], 400);
        }

        $roleString = $data['role'];

        $role = GuildRole::tryFrom($roleString);
        if (!$role) {
            return $this->json(['message' => 'Rôle invalide. Valeurs acceptées: Member, Officer, GM'], 400);
        }

        $guildMember->setRole($role);
        $this->entityManager->flush();

        return $this->json(GuildMembershipDTO::fromEntity($guildMember));
    }
}
