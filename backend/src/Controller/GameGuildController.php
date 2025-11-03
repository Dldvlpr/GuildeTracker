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

    #[Route('/api/guilds', name: 'api_guild_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createGuild(
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
            // A user can create a guild but will be MEMBER by default.
            // GM/Officer can later claim to elevate their role.
            $membership = new GuildMembership($user, $gameGuild, GuildRole::MEMBER);

            $em->persist($gameGuild);
            $em->persist($membership);
            $em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Database error', 'hint' => $e->getMessage()], 500);
        }

        return $this->json(['status' => 'ok', 'id' => $gameGuild->getUuidToString()], 201);
    }

    #[Route('/api/guilds/exists', name: 'api_guild_exists', methods: ['GET'])]
    public function guildExists(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('name', ''));
        $realm = $request->query->get('realm');
        if ($name === '') {
            return $this->json(['error' => 'Missing "name"'], 400);
        }
        $realmStr = $realm !== null ? (string) $realm : null;
        $existing = $this->gameGuildRepository->findOneByRealmAndNameInsensitive($realmStr, $name);
        if (!$existing) {
            return $this->json(['exists' => false]);
        }
        return $this->json(['exists' => true, 'id' => $existing->getUuidToString(), 'name' => $existing->getName()]);
    }

    #[Route('/api/guilds/{id}/characters', name: 'api_guild_characters_list', methods: ['GET'])]
    public function listGuildCharacters(string $id): JsonResponse
    {
        $guild = $this->gameGuildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);

        return $this->json(CharacterDTO::fromEntities($guild->getGameCharacters()));
    }

    #[Route('/api/guilds/{id}', name: 'api_guild_show', methods: ['GET'])]
    public function showGuild(string $id): JsonResponse
    {
        $guild = $this->gameGuildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);

        return $this->json(GameGuildDTO::fromEntity($guild));
    }
}
