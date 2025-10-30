<?php

namespace App\Controller;

use App\Entity\User;
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
    ) {}

    #[Route('/api/oauth/blizzard/connect', name: 'connect_blizzard_start', methods: ['GET'])]
    public function connect(Request $request, RateLimiterFactory $blizzardOauthStartLimiter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $limiter = $blizzardOauthStartLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            throw $this->createAccessDeniedException('Too many requests');
        }

        return $this->clientRegistry->getClient('blizzard')->redirect(['wow.profile']);
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
            $accessToken = $client->getAccessToken();
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
}

