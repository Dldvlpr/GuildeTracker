<?php

namespace App\Controller;

use App\DTO\GuildMembershipDTO;
use App\Entity\GameGuild;
use App\Repository\GameGuildRepository;
use App\Repository\GuildMembershipRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class GuildMembershipController extends AbstractController
{
    public function __construct(
        private readonly GuildMembershipRepository $guildMembershipRepository,
        private readonly GameGuildRepository $gameGuildRepository,
        private readonly userRepository $userRepository,
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
}
