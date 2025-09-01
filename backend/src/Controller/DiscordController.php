<?php

namespace App\Controller;

use App\Repository\UserRepository;
use GuzzleHttp\Client;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;

class DiscordController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $token = $request->cookies->get('DISCORD_TOKEN');
        if (!$token) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $client = new Client();
        try {
            $response = $client->get('https://discord.com/api/users/@me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->json([
                'id'       => $data['id'],
                'username' => $data['username'],
                'email'    => $data['email'] ?? null,
                'avatar'   => $data['avatar'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Token Discord invalide ou expiré'], 401);
        }
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

        error_log('Response status: 200');
        return $response;
    }

    #[Route('/connect/discord', name: 'connect_discord_start', methods: ['GET'])]
    public function connect(Request $request, ClientRegistry $clientRegistry, RateLimiterFactory $discordOauthStart): RedirectResponse|JsonResponse
    {
        $limiter = $discordOauthStart->create($request->getClientIp() ?? 'none');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Too many request'], 429);
        }

        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=/');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=/');

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
    public function connectCheck(
        Request $request,
        ClientRegistry $clients,
        RateLimiterFactory $discord_oauth_callback,
        UserRepository $users,
        EntityManagerInterface $em,
    ): RedirectResponse
    {
        // Rate limit
        $limiter = $discord_oauth_callback->create($request->getClientIp() ?? 'anon');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->redirect('https://app.example.com/auth/error?reason=rate_limited');
        }

        $client = $clients->getClient('discord');

        try {
            $verifier = $request->getSession()->get('discord_pkce_verifier');
            $request->getSession()->remove('discord_pkce_verifier');

            // Échange code -> tokens avec PKCE
            $accessToken = $client->getAccessToken(['code_verifier' => $verifier]);

            // Profil @me
            $discordUser = $client->fetchUserFromToken($accessToken);
            $discordId = (string) $discordUser->getId();
            $arr = $discordUser->toArray();

            // Upsert user + stocker tokens SERVER-SIDE (pas en cookie front)
            $user = $users->findOneBy(['discordId' => $discordId]) ?? new \App\Entity\User();
            $user->setDiscordId($discordId);
            $user->setUsername($arr['username'] ?? null);
            $user->setEmail($arr['email'] ?? null);
            $user->setAvatar($arr['avatar'] ?? null);
            $user->setDiscordAccessToken($accessToken->getToken());
            $user->setDiscordRefreshToken($accessToken->getRefreshToken());
            $user->setDiscordTokenExpiresAt((new \DateTimeImmutable())->setTimestamp($accessToken->getExpires() ?? (time()+3600)));

            $em->persist($user);
            $em->flush();

            // Pose UNIQUEMENT ta session applicative (HttpOnly). Pas de token Discord côté navigateur.
            $response = $this->redirect($this->getParameter('front.success_uri'));
            $response->headers->setCookie(
                cookie(
                    name: 'APP_SESSION',
                    value: base64_encode(json_encode(['uid' => $user->getId()])), // idéalement: id de session opaque signé
                    expires: 0,
                    path: '/',
                    domain: null,
                    secure: true,
                    httpOnly: true,
                    sameSite: 'Lax'
                )
            );
            return $response;
        } catch (\Throwable $e) {
            return $this->redirect($this->getParameter('front.success_error'));
        }
    }
}
