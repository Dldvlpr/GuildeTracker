<?php

namespace App\Controller;

use App\DTO\GameGuildDTO;
use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Entity\User;
use App\Repository\GameGuildRepository;
use App\Repository\GuildMembershipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(private readonly GameGuildRepository $gameGuildRepository)
    {
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getDiscordId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/api/me/guilds', name: 'api_me_guild', methods: ['GET'])]
    public function meGuilds(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $guild = $this->gameGuildRepository->findByUserId($user->getUuidToString());

        $response = GameGuildDTO::fromEntities($guild) ?? [];
        return $this->json($response, 201);
    }
}
