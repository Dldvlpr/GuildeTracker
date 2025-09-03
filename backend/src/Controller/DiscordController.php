<?php

namespace App\Controller;

use App\Repository\UserRepository;
use GuzzleHttp\Client;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DiscordController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(Request $request, UserRepository $users): JsonResponse
    {
        error_log('[/api/me] Cookies: '.json_encode($request->cookies->all()));

        $sessionCookie = $request->cookies->get('APP_SESSION');
        if (!$sessionCookie) {
            return $this->json(['error' => 'Non authentifié (APP_SESSION manquant)'], 401);
        }

        $decoded = base64_decode($sessionCookie, true);
        if ($decoded === false) {
            return $this->json(['error' => 'Non authentifié (base64 invalide)', 'raw' => $sessionCookie], 401);
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Non authentifié (json invalide)', 'decoded' => $decoded], 401);
        }
        if (!array_key_exists('uid', $payload)) {
            return $this->json(['error' => 'Non authentifié (uid manquant)', 'payload' => $payload], 401);
        }

        $uid = $payload['uid'] ?? null;
        if (is_int($uid)) {
            $uidRaw = $uid;
        } elseif (is_string($uid) && ctype_digit($uid)) {
            $uidRaw = (int) $uid;
        } else {
            return $this->json(['error' => 'Non authentifié (uid non numérique)', 'uid' => $payload['uid']], 401);
        }

        error_log('[/api/me] Looking up user id=' . $uidRaw);

        $user = $users->find($uidRaw);
        if (!$user) {
            return $this->json(['error' => 'Non authentifié (user introuvable)', 'uid' => $uidRaw], 401);
        }

        return $this->json([
            'id'       => $user->getDiscordId(),
            'username' => $user->getUsername(),
            'email'    => $user->getEmail(),
            'avatar'   => $user->getAvatar(),
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['GET', 'POST'])]
    public function logout(Request $request): JsonResponse
    {
        error_log('=== LOGOUT DEBUG ===');
        error_log('Method: ' . $request->getMethod());
        error_log('URL: ' . $request->getUri());
        error_log('Headers: ' . json_encode($request->headers->all()));
        error_log('Cookies: ' . json_encode($request->cookies->all()));

        $token = $request->cookies->get('DISCORD_TOKEN');
        error_log('Discord Token: ' . ($token ? 'Present' : 'Missing'));

        $response = new JsonResponse(['message' => 'Déconnexion réussie'], 200);

        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin', 'https://localhost:5173'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        $response->headers->clearCookie(
            'DISCORD_TOKEN',
            '/',
            null,
            true,
            true,
            'Lax'
        );

        // Also clear our app session cookie used by /api/me
        $response->headers->clearCookie(
            'APP_SESSION',
            '/',
            null,
            true,
            true,
            'None'
        );

        error_log('Response status: 200');
        return $response;
    }

    #[Route('/connect/discord', name: 'connect_discord_start', methods: ['GET'])]
    public function connect(
        Request $request,
        ClientRegistry $clientRegistry,
        #[Autowire(service: 'limiter.discord_oauth_start')] RateLimiterFactory $discordLimiter
    ): RedirectResponse|JsonResponse {
        $limiter = $discordLimiter->create($request->getClientIp() ?? 'none');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Too many request'], 429);
        }

        $codeVerifier  = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $request->getSession()->set('discord_pkce_verifier', $codeVerifier);

        return $clientRegistry->getClient('discord')->redirect(
            ['identify', 'email'],
            [
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
            ]
        );
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check', methods: ['GET'])]
    public function connectCheck(): void
    {
        // Handled by App\\Security\\DiscordAuthenticator
        throw new \LogicException('The Discord OAuth callback must be handled by the security authenticator.');
    }

    #[Route('/debug/cookies', name: 'debug_cookies', methods: ['GET'])]
    public function debugCookies(Request $request): JsonResponse
    {
        return $this->json([
            'cookies' => $request->cookies->all(),
            'headers' => $request->headers->all(),
        ]);
    }
}
