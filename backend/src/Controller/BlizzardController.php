<?php

namespace App\Controller;

use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Entity\User;
use App\Enum\GuildRole;
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
    public function getCharacterDetails(string $realm, string $characterName): Response
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
            $profile = $this->blizzardService->getCharacterProfile($accessToken, $realm, $characterName);
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
            // Get character profile to check guild membership
            $characterProfile = $this->blizzardService->getCharacterProfile($accessToken, $realm, $characterName);

            if (!isset($characterProfile['guild'])) {
                return $this->json([
                    'error' => 'no_guild',
                    'message' => 'This character is not in a guild'
                ], Response::HTTP_NOT_FOUND);
            }

            // Extract guild info from character profile
            $guildInfo = $characterProfile['guild'];
            $guildName = $guildInfo['name'] ?? null;
            $guildFaction = $characterProfile['faction']['name'] ?? 'Unknown';
            $guildId = $guildInfo['id'] ?? null;

            // Verify the character is GM (rank 0)
            $isGuildMaster = $this->blizzardService->isGuildMaster($accessToken, $realm, $characterName);

            if (!$isGuildMaster) {
                return $this->json([
                    'error' => 'not_guild_master',
                    'message' => 'You must be the Guild Master (rank 0) to claim this guild'
                ], Response::HTTP_FORBIDDEN);
            }

            if (!$guildName || !$guildId) {
                return $this->json([
                    'error' => 'invalid_guild_data',
                    'message' => 'Unable to retrieve guild information from Blizzard'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Check if guild already claimed by someone else
            $existingGuild = $this->gameGuildRepository->findOneBy([
                'blizzardId' => (string) $guildId,
                'realm' => $realm
            ]);

            if ($existingGuild) {
                // Check if current user is already GM of this guild
                foreach ($existingGuild->getGuildMemberships() as $membership) {
                    if ($membership->getUser()->getId() === $user->getId() && $membership->getRole() === GuildRole::GM) {
                        return $this->json([
                            'error' => 'already_claimed_by_you',
                            'message' => 'You have already claimed this guild',
                            'guild_id' => $existingGuild->getUuidToString()
                        ], Response::HTTP_CONFLICT);
                    }
                }

                return $this->json([
                    'error' => 'already_claimed',
                    'message' => 'This guild has already been claimed by another user'
                ], Response::HTTP_CONFLICT);
            }

            // Create new guild
            $gameGuild = new GameGuild();
            $gameGuild->setName($guildName);
            $gameGuild->setFaction($guildFaction);
            $gameGuild->setRealm($realm);
            $gameGuild->setBlizzardId((string) $guildId);
            $gameGuild->setIsPublic(false);
            $gameGuild->setShowDkpPublic(false);

            // Create GM membership
            $membership = new GuildMembership($user, $gameGuild, GuildRole::GM);

            $this->em->persist($gameGuild);
            $this->em->persist($membership);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Guild claimed successfully',
                'guild' => [
                    'id' => $gameGuild->getUuidToString(),
                    'name' => $gameGuild->getName(),
                    'faction' => $gameGuild->getFaction(),
                    'realm' => $gameGuild->getRealm(),
                    'blizzard_id' => $gameGuild->getBlizzardId()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            error_log("Failed to claim guild: {$e->getMessage()}");
            return $this->json([
                'error' => 'claim_failed',
                'message' => 'An error occurred while claiming the guild'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
