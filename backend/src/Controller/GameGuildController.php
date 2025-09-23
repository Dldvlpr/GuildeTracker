<?php

namespace App\Controller;

use App\DTO\CharacterDTO;
use App\DTO\GameGuildDTO;
use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Enum\GuildRole;
use App\Form\GameGuildType;
use App\Repository\GameCharacterRepository;
use App\Repository\GameGuildRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class GameGuildController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GameGuildRepository $gameGuildRepository,
        private readonly UserRepository $usersRepository,
        private readonly GameCharacterRepository $gameCharacterRepository,
    ) {}

    #[Route('/api/gameguild/create', name: 'gameGuild_create', methods: ['POST'])]
    public function createGameGuild(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        $gameGuild = new GameGuild();

        $form = $this->createForm(GameGuildType::class, $gameGuild, [
            'csrf_protection' => false,
        ]);
        $form->submit($payload, true);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Validation failed',
                'details' => $errors,
                'violations' => $errors
            ], 422);
        }

        $securityUser = $this->getUser();
        if (!$securityUser) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }

        $user = $this->usersRepository->findOneBy(['discordId' => $securityUser->getDiscordId()]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        try {
            $membership = new GuildMembership($user, $gameGuild, GuildRole::GM);

            $em->persist($gameGuild);
            $em->persist($membership);
            $em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Database error', 'hint' => $e->getMessage()], 500);
        }

        return $this->json(['status' => 'ok', 'id' => $gameGuild->getUuidToString()], 201);
    }

    #[Route('/api/gameguild/{id}/members', name: 'getGameGuildMembers', methods: ['GET'])]
    public function getGameguildMembers(string $id): JsonResponse
    {
        $guild = $this->gameGuildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);

        return $this->json(CharacterDTO::fromEntities($guild->getGameCharacters()));
    }

    #[Route('/api/gameguild', name: 'get_allGameGuild', methods: ['GET'])]
    public function getGameGuild(): JsonResponse
    {
        $gameGuilds = $this->gameGuildRepository->findAll();
        $response = GameGuildDTO::fromEntities($gameGuilds);

        return $this->json($response, 201);
    }
}
