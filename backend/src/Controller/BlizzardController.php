<?php

namespace App\Controller;

use App\Entity\BlizzardGameRealm;
use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Entity\User;
use App\Enum\GuildRole;
use App\Enum\WowGameType;
use App\Repository\BlizzardGameRealmRepository;
use App\Repository\GameGuildRepository;
use App\Service\BlizzardService;
use App\Service\TokenEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BlizzardController extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly HttpClientInterface $http,
        private readonly EntityManagerInterface $em,
        private readonly ParameterBagInterface $params,
        private readonly TokenEncryptionService $tokenEncryptionService,
        private readonly BlizzardService $blizzardService,
        private readonly GameGuildRepository $gameGuildRepository,
        private readonly BlizzardGameRealmRepository $blizzardGameRealmRepository,
    ) {}

    #[Route('/api/oauth/blizzard/connect', name: 'connect_blizzard_start', methods: ['GET'])]
    public function connect(Request $request, RateLimiterFactory $blizzardOauthStartLimiter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $limiter = $blizzardOauthStartLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            throw $this->createAccessDeniedException('Too many requests');
        }

        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $request->getSession()->set('blizzard_pkce_verifier', $verifier);

        return $this->clientRegistry->getClient('blizzard')->redirect([
            'wow.profile'
        ], [
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    #[Route('/api/oauth/blizzard/callback', name: 'connect_blizzard_check', methods: ['GET'])]
    public function callback(Request $request, RateLimiterFactory $blizzardOauthCallbackLimiter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $limiter = $blizzardOauthCallbackLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            throw $this->createAccessDeniedException('Too many requests');
        }

        $client = $this->clientRegistry->getClient('blizzard');

        try {
            $verifier = $request->getSession()->get('blizzard_pkce_verifier');
            $request->getSession()->remove('blizzard_pkce_verifier');
            $opts = $verifier ? ['code_verifier' => $verifier] : [];
            $accessToken = $client->getAccessToken($opts);
            $token = $accessToken->getToken();
            $refreshToken = $accessToken->getRefreshToken();
            $expiresAt = $accessToken->getExpires();

            $encryptedToken = $this->tokenEncryptionService->encrypt($token);
            $encryptedRefresh = $refreshToken ? $this->tokenEncryptionService->encrypt($refreshToken) : null;
            $expiresDate = new \DateTimeImmutable('@' . $expiresAt);

        } catch (\Throwable $e) {
            error_log("Blizzard token error: {$e->getMessage()}");
            $errorUrl = (string) $this->params->get('front.error_uri');
            $glue = str_contains($errorUrl, '?') ? '&' : '?';
            return new RedirectResponse($errorUrl . $glue . 'reason=bnet_token');
        }

        $region = (string) $this->params->get('blizzard.region');
        $locale = (string) $this->params->get('blizzard.locale');

        $base = sprintf('https://%s.api.blizzard.com', $region);
        $namespace = sprintf('profile-%s', $region);
        $url = sprintf('%s/profile/user/wow?namespace=%s&locale=%s', $base, $namespace, $locale);

        try {
            $resp = $this->http->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken->getToken(),
                ],
            ]);

            if (401 === $resp->getStatusCode()) {
                throw new \RuntimeException('Unauthorized from Blizzard API');
            }

            $data = $resp->toArray(false);
            $accountId = $data['id'] ?? null;

            if (!$accountId) {
                throw new \RuntimeException('No Blizzard account id returned');
            }

            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new \RuntimeException('User not resolved');
            }

            $user->setBlizzardId((string) $accountId);
            $user->setBlizzardAccessToken($encryptedToken);
            $user->setBlizzardRefreshToken($encryptedRefresh);
            $user->setBlizzardTokenExpiresAt($expiresDate);

            $this->em->persist($user);
            $this->em->flush();

            $successUrl = (string) $this->params->get('front.success_uri');
            $glue = str_contains($successUrl, '?') ? '&' : '?';
            return new RedirectResponse($successUrl . $glue . 'linked=blizzard');
        } catch (\Throwable $e) {
            error_log("Blizzard profile fetch failed: {$e->getMessage()}");
            $errorUrl = (string) $this->params->get('front.error_uri');
            $glue = str_contains($errorUrl, '?') ? '&' : '?';

            return new RedirectResponse($errorUrl . $glue . 'reason=bnet_profile');
        }
    }

    #[Route('/api/blizzard/characters', name: 'blizzard_characters', methods: ['GET'])]
    public function getBlizzardCharacters(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getBlizzardId()) {
            return $this->json([
                'error' => 'Blizzard account not linked',
                'message' => 'Please link your Battle.net account first'
            ], Response::HTTP_FORBIDDEN);
        }

        $accessToken = $this->blizzardService->getValidAccessToken($user);

        if (!$accessToken) {
            return $this->json([
                'error' => 'blizzard_token_expired',
                'message' => 'Your Battle.net session has expired. Please reconnect.',
                'reconnect_url' => '/api/oauth/blizzard/connect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $characters = $this->blizzardService->getWowCharacters($accessToken);
            return $this->json($characters);
        } catch (\Throwable $e) {
            error_log("Failed to fetch WoW characters: {$e->getMessage()}");
            return $this->json([
                'error' => 'Failed to fetch characters',
                'message' => 'An error occurred while retrieving your characters'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/blizzard/characters/{realm}/{characterName}', name: 'blizzard_character_details', methods: ['GET'])]
    public function getCharacterDetails(string $realm, string $characterName, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $this->blizzardService->getValidAccessToken($user);

        if (!$accessToken) {
            return $this->json([
                'error' => 'blizzard_token_expired',
                'message' => 'Your Battle.net session has expired. Please reconnect.',
                'reconnect_url' => '/api/oauth/blizzard/connect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $wowType = $request->query->get('wowType', 'Retail');

        try {
            $profile = $this->blizzardService->getCharacterProfile($accessToken, $realm, $characterName, $wowType);
            return $this->json($profile);
        } catch (\Throwable $e) {
            error_log("Failed to fetch character profile: {$e->getMessage()}");
            return $this->json([
                'error' => 'Failed to fetch character',
                'message' => 'An error occurred while retrieving character details'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/blizzard/characters/{realm}/{characterName}/guild', name: 'blizzard_character_guild', methods: ['GET'])]
    public function getCharacterGuild(string $realm, string $characterName): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $this->blizzardService->getValidAccessToken($user);

        if (!$accessToken) {
            return $this->json([
                'error' => 'blizzard_token_expired',
                'message' => 'Your Battle.net session has expired. Please reconnect.',
                'reconnect_url' => '/api/oauth/blizzard/connect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $guildData = $this->blizzardService->getCharacterGuild($accessToken, $realm, $characterName);

            if (!$guildData) {
                return $this->json([
                    'error' => 'Character has no guild',
                    'message' => 'This character is not in a guild'
                ], Response::HTTP_NOT_FOUND);
            }

            $isGuildMaster = $this->blizzardService->isGuildMaster($accessToken, $realm, $characterName);

            return $this->json([
                'guild' => $guildData,
                'isGuildMaster' => $isGuildMaster,
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to fetch character guild: {$e->getMessage()}");
            return $this->json([
                'error' => 'Failed to fetch guild',
                'message' => 'An error occurred while retrieving guild information'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/guilds/claim', name: 'blizzard_guild_claim', methods: ['POST'])]
    public function claimGuild(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getBlizzardId()) {
            return $this->json([
                'error' => 'blizzard_not_linked',
                'message' => 'Please link your Battle.net account first',
                'link_url' => '/api/oauth/blizzard/connect'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $realm = $payload['realm'] ?? null;
        $characterName = $payload['characterName'] ?? null;
        $wowType = $payload['wowType'] ?? 'Retail';

        if (!$realm || !$characterName) {
            return $this->json([
                'error' => 'Missing required fields',
                'message' => 'Both realm and characterName are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $accessToken = $this->blizzardService->getValidAccessToken($user);

        if (!$accessToken) {
            return $this->json([
                'error' => 'blizzard_token_expired',
                'message' => 'Your Battle.net session has expired. Please reconnect.',
                'reconnect_url' => '/api/oauth/blizzard/connect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $characterProfile = $this->blizzardService->getCharacterProfile($accessToken, $realm, $characterName, $wowType);

            if (!isset($characterProfile['guild'])) {
                return $this->json([
                    'error' => 'no_guild',
                    'message' => 'This character is not in a guild'
                ], Response::HTTP_NOT_FOUND);
            }

            $guildInfo = $characterProfile['guild'];
            $guildName = $guildInfo['name'] ?? null;
            $guildFaction = $characterProfile['faction']['name'] ?? 'Unknown';
            $guildId = $guildInfo['id'] ?? null;

            if (!$guildName || !$guildId) {
                return $this->json([
                    'error' => 'invalid_guild_data',
                    'message' => 'Unable to retrieve guild information from Blizzard'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            error_log("Claiming guild - Realm: {$realm}, Character: {$characterName}, WowType: {$wowType}, Guild: {$guildName}");
            $rank = $this->blizzardService->getGuildRank($accessToken, $realm, $characterName, $wowType);

            $isClassic = str_contains($wowType, 'Classic');

            if ($rank === null && !$isClassic) {
                error_log("Rank is null for {$characterName} in guild {$guildName} (Retail)");
                return $this->json([
                    'error' => 'rank_unknown',
                    'message' => 'Unable to determine your rank in the guild. Check server logs for more details.'
                ], Response::HTTP_FORBIDDEN);
            }

            if ($rank === null && $isClassic) {
                error_log("Classic guild detected - assigning MEMBER role by default for {$characterName}");
                $role = GuildRole::MEMBER;
            } else {
                error_log("Successfully got rank {$rank} for {$characterName}");
                $role = $rank === 0 ? GuildRole::GM : ($rank === 1 ? GuildRole::OFFICER : GuildRole::MEMBER);
            }

            $blizzardRealm = $this->findOrCreateBlizzardRealm($realm, $wowType);

            if (!$blizzardRealm) {
                return $this->json([
                    'error' => 'realm_not_found',
                    'message' => sprintf(
                        'Realm "%s" not found for %s. Please contact support or try syncing realms.',
                        $realm,
                        $wowType
                    )
                ], Response::HTTP_NOT_FOUND);
            }

            $existingGuild = $this->gameGuildRepository->findOneBy([
                'name' => $guildName,
                'blizzardRealm' => $blizzardRealm,
            ]);

            if (!$existingGuild) {
                $existingGuild = $this->gameGuildRepository->findOneBy([
                    'blizzardId' => (string) $guildId,
                    'realm' => $realm
                ]);

                if ($existingGuild) {
                    $existingGuild->setBlizzardRealm($blizzardRealm);
                    $this->em->persist($existingGuild);
                }
            }

            if ($existingGuild) {
                $hasMembership = false;
                foreach ($existingGuild->getGuildMemberships() as $m) {
                    if ($m->getUser()->getId() === $user->getId()) {
                        $hasMembership = true;
                        if ($m->getRole() !== $role) {
                            $m->setRole($role);
                            $this->em->persist($m);
                        }
                        break;
                    }
                }
                if (!$hasMembership) {
                    $this->em->persist(new GuildMembership($user, $existingGuild, $role));
                }

                $this->em->flush();

                try { $this->blizzardService->importRosterIntoGuild($accessToken, $realm, $guildName, $existingGuild, $wowType); } catch (\Throwable) {}

                return $this->json([
                    'success' => true,
                    'message' => 'Guild claimed successfully',
                    'guild' => $this->formatGuildResponse($existingGuild)
                ], Response::HTTP_OK);
            }

            $gameGuild = new GameGuild();
            $gameGuild->setName($guildName);
            $gameGuild->setFaction($guildFaction);
            $gameGuild->setBlizzardRealm($blizzardRealm);
            $gameGuild->setIsPublic(false);
            $gameGuild->setShowDkpPublic(false);

            $membership = new GuildMembership($user, $gameGuild, $role);

            $this->em->persist($gameGuild);
            $this->em->persist($membership);
            $this->em->flush();

            try { $this->blizzardService->importRosterIntoGuild($accessToken, $realm, $guildName, $gameGuild, $wowType); } catch (\Throwable) {}

            return $this->json([
                'success' => true,
                'message' => 'Guild claimed successfully',
                'guild' => $this->formatGuildResponse($gameGuild)
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            error_log("Failed to claim guild: {$e->getMessage()}");

            if (str_contains($e->getMessage(), 'Not Found') || str_contains($e->getMessage(), '404')) {
                return $this->json([
                    'error' => 'character_not_found',
                    'message' => sprintf(
                        'Character "%s" not found on realm "%s". Please verify the character name and realm are correct, or try a different character.',
                        $characterName,
                        $realm
                    )
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'error' => 'claim_failed',
                'message' => 'An error occurred while claiming the guild: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/guilds/{id}/join', name: 'guild_join', methods: ['POST'])]
    public function joinGuild(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $guild = $this->gameGuildRepository->find($id);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->getBlizzardId()) {
            return $this->json([
                'error' => 'blizzard_not_linked',
                'message' => 'Please link your Battle.net account first',
                'link_url' => '/api/oauth/blizzard/connect'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            $payload = [];
        }

        $realm = $payload['realm'] ?? null;
        $characterName = $payload['characterName'] ?? null;
        $wowType = $payload['wowType'] ?? 'Retail';

        if (!$realm || !$characterName) {
            return $this->json([
                'error' => 'Missing required fields',
                'message' => 'Both realm and characterName are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $accessToken = $this->blizzardService->getValidAccessToken($user);
        if (!$accessToken) {
            return $this->json([
                'error' => 'blizzard_token_expired',
                'message' => 'Your Battle.net session has expired. Please reconnect.',
                'reconnect_url' => '/api/oauth/blizzard/connect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $blizzardRealmId = $guild->getBlizzardRealm()?->getId() ?? 'unknown';
            $guildBlizzardId = $guild->getBlizzardId() ?? 'unknown';

            error_log("joinGuild: realm={$realm}, char={$characterName}, wowType={$wowType}, targetGuildId={$guildBlizzardId}, blizzardRealmId={$blizzardRealmId}");

            $characterProfile = $this->blizzardService->getCharacterProfile($accessToken, $realm, $characterName, $wowType);

            if (!isset($characterProfile['guild'])) {
                error_log("joinGuild: Character {$characterName} has no guild");
                return $this->json([
                    'error' => 'no_guild',
                    'message' => 'This character is not in a guild'
                ], Response::HTTP_BAD_REQUEST);
            }

            $charGuildName = $characterProfile['guild']['name'] ?? '';

            error_log("joinGuild: Character's guild name={$charGuildName}");

            if (strcasecmp($guild->getName() ?? '', $charGuildName) !== 0) {
                error_log("joinGuild: Guild name mismatch - expected {$guild->getName()}, got {$charGuildName}");
                return $this->json([
                    'error' => 'guild_mismatch',
                    'message' => 'This character does not belong to the target guild.'
                ], Response::HTTP_FORBIDDEN);
            }

            foreach ($guild->getGuildMemberships() as $m) {
                if ($m->getUser()->getId() === $user->getId()) {
                    error_log("joinGuild: User already a member");
                    return $this->json(['message' => 'Already a member'], Response::HTTP_OK);
                }
            }

            $this->em->persist(new GuildMembership($user, $guild, GuildRole::MEMBER));
            $this->em->flush();

            error_log("joinGuild: Successfully joined guild");
            return $this->json(['message' => 'Joined guild successfully'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            error_log("joinGuild exception: " . $e->getMessage());
            return $this->json([
                'error' => 'join_failed',
                'message' => 'An error occurred while joining the guild: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Find or create BlizzardGameRealm based on realm slug and WoW type
     */
    private function findOrCreateBlizzardRealm(string $realmSlug, string $wowType): ?BlizzardGameRealm
    {
        $region = (string) $this->params->get('blizzard.region');

        $slug = strtolower(str_replace(' ', '-', $realmSlug));

        $gameType = WowGameType::fromString($wowType);

        $realm = $this->blizzardGameRealmRepository->findOneBy([
            'slug' => $slug,
            'gameType' => $gameType,
            'region' => $region,
        ]);

        if ($realm) {
            return $realm;
        }

        error_log(sprintf(
            'BlizzardGameRealm not found for slug=%s, gameType=%s, region=%s. Run app:sync-blizzard-realms',
            $slug,
            $gameType->value,
            $region
        ));

        return null;
    }

    private function formatGuildResponse(GameGuild $guild): array
    {
        $realm = $guild->getBlizzardRealm();

        return [
            'id' => $guild->getUuidToString(),
            'name' => $guild->getName(),
            'faction' => $guild->getFaction(),
            'realm' => $realm ? [
                'id' => $realm->getId(),
                'name' => $realm->getName(),
                'slug' => $realm->getSlug(),
                'game_type' => $realm->getGameType()->value,
                'game_type_label' => $realm->getGameType()->getLabel(),
                'region' => $realm->getRegion(),
            ] : null,
            'realm_name' => $realm?->getName(),
            'game_type' => $realm?->getGameType()->getLabel(),
        ];
    }
}
