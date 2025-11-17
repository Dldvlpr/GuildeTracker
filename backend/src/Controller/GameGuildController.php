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
use App\Service\WowClassMapper;
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
        private readonly WowClassMapper $classMapper,
        private readonly \App\Service\BlizzardService $blizzardService,
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

        return $this->json(CharacterDTO::fromEntities($guild->getGameCharacters(), $this->classMapper));
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

    #[Route('/api/guilds/{id}/sync', name: 'api_guild_sync', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function syncGuild(string $id): JsonResponse
    {
        $guild = $this->gameGuildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $guild);

        $securityUser = $this->getUser();
        if (!$securityUser) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }

        $user = $this->usersRepository->findOneBy(['discordId' => $securityUser->getDiscordId()]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $accessToken = $this->blizzardService->getValidAccessToken($user);
        if (!$accessToken) {
            return $this->json([
                'error' => 'blizzard_token_expired',
                'message' => 'Your Battle.net session has expired. Please reconnect.',
                'reconnect_url' => '/api/oauth/blizzard/connect'
            ], 401);
        }

        $realm = $guild->getRealm() ?? $guild->getBlizzardRealm()?->getSlug();
        $guildName = $guild->getName();

        if (!$realm || !$guildName) {
            return $this->json(['error' => 'Guild missing realm or name'], 400);
        }

        $wowType = 'Retail';
        if ($blizzardRealm = $guild->getBlizzardRealm()) {
            $wowType = match ($blizzardRealm->getGameType()?->value) {
                'classic-anniversary' => 'Classic Anniversary',
                'classic-era' => 'Classic Era',
                'classic-progression' => 'Classic Progression',
                'season-of-discovery' => 'Season of Discovery',
                'hardcore' => 'Hardcore',
                default => 'Retail',
            };
        }

        try {
            $result = $this->blizzardService->syncGuildRoster(
                $accessToken,
                $realm,
                $guildName,
                $guild,
                $wowType,
                fetchSpecs: true,
                preserveManualRoles: true
            );

            return $this->json([
                'success' => true,
                'message' => 'Guild roster synchronized',
                'created' => $result['created'],
                'updated' => $result['updated'],
                'removed' => $result['removed'],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'sync_failed',
                'message' => 'Failed to sync guild roster: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/guilds/{guildId}/characters/{characterId}/role', name: 'api_guild_character_update_role', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateCharacterRole(string $guildId, string $characterId, Request $request): JsonResponse
    {
        $guild = $this->gameGuildRepository->find($guildId);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $guild);

        $character = $this->gameCharacterRepository->find($characterId);
        if (!$character || $character->getGuild()?->getId()->toString() !== $guildId) {
            return $this->json(['error' => 'Character not found in this guild'], 404);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        if (isset($payload['role'])) {
            $newRole = $payload['role'];
            if (!in_array($newRole, ['Tank', 'Healer', 'DPS', 'Unknown'])) {
                return $this->json(['error' => 'Invalid role. Must be: Tank, Healer, DPS, or Unknown'], 400);
            }
            $character->setRole($newRole);
        }

        if (isset($payload['spec'])) {
            $newSpec = trim($payload['spec']);
            if (empty($newSpec)) {
                $newSpec = 'Unknown';
            }
            $character->setClassSpec($newSpec);
        }

        if (isset($payload['specSecondary'])) {
            $newSpec2 = trim((string)$payload['specSecondary']);
            if ($newSpec2 === '') { $newSpec2 = null; }
            $character->setClassSpecSecondary($newSpec2);
        }

        if (!isset($payload['role']) && !isset($payload['spec']) && !isset($payload['specSecondary'])) {
            return $this->json(['error' => 'At least one field (role or spec) must be provided'], 400);
        }

        $this->em->persist($character);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Character updated successfully',
            'character' => CharacterDTO::fromEntity($character, $this->classMapper)
        ]);
    }

    #[Route('/api/guilds/{id}/relink-my-characters', name: 'api_guild_relink_my_characters', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function relinkMyCharacters(string $id): JsonResponse
    {
        $guild = $this->gameGuildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);

        $securityUser = $this->getUser();
        if (!$securityUser) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }

        $user = $this->usersRepository->findOneBy(['discordId' => $securityUser->getDiscordId()]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if (!$user->getBlizzardId()) {
            return $this->json([
                'error' => 'blizzard_not_linked',
                'message' => 'Please link your Battle.net account first',
                'link_url' => '/api/oauth/blizzard/connect'
            ], 403);
        }

        $accessToken = $this->blizzardService->getValidAccessToken($user);
        if (!$accessToken) {
            return $this->json([
                'error' => 'blizzard_token_expired',
                'message' => 'Your Battle.net session has expired. Please reconnect.',
                'reconnect_url' => '/api/oauth/blizzard/connect'
            ], 401);
        }

        try {
            $list = $this->blizzardService->getWowCharacters($accessToken);
            $chars = is_array($list['characters'] ?? null) ? $list['characters'] : [];
            $names = [];
            foreach ($chars as $c) {
                $n = $c['name'] ?? null;
                if ($n) { $names[strtolower($n)] = true; }
            }

            $linked = 0;
            foreach ($guild->getGameCharacters() as $gc) {
                $n = strtolower($gc->getName() ?? '');
                if ($n !== '' && isset($names[$n])) {
                    if ($gc->getUserPlayer()?->getId() !== $user->getId()) {
                        $gc->setUserPlayer($user);
                        $this->em->persist($gc);
                        $linked++;
                    }
                }
            }
            if ($linked > 0) {
                $this->em->flush();
            }

            return $this->json([
                'success' => true,
                'message' => $linked > 0 ? 'Characters relinked' : 'No characters to relink',
                'linked' => $linked,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'relink_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
