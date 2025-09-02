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
    public function connectCheck(
        Request $request,
        ClientRegistry $clients,
        #[Autowire(service: 'limiter.discord_oauth_callback')] RateLimiterFactory $discordCallbackLimiter,
        UserRepository $users,
        \Doctrine\ORM\EntityManagerInterface $em,
    ) {
        $limiter = $discordCallbackLimiter->create($request->getClientIp() ?? 'anon');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->redirect($this->getParameter('front.error_uri') . '?reason=oauth_failed');
        }

        $client = $clients->getClient('discord');

        try {
            $verifier = $request->getSession()->get('discord_pkce_verifier');
            $request->getSession()->remove('discord_pkce_verifier');

            $accessToken = $client->getAccessToken(['code_verifier' => $verifier]);

            $discordUser = $client->fetchUserFromToken($accessToken);
            $discordId = (string) $discordUser->getId();
            $arr = $discordUser->toArray();

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

            $cookie = Cookie::create('APP_SESSION')
                ->withValue(base64_encode(json_encode(['uid' => (int) $user->getId()])))
                ->withPath('/')
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('None');

            $frontSuccess = $this->getParameter('front.success_uri');

            $html = <<<HTML
<!doctype html>
<meta charset="utf-8">
<title>Connexion…</title>
<p>Connexion réussie. Redirection…</p>
<script>
  window.location.replace({json_encode($frontSuccess)});
</script>
HTML;

            $response = new Response($html, 200);
            $response->headers->setCookie($cookie);
            $response->headers->clearCookie('DISCORD_TOKEN', '/');
            return $response;
        } catch (\Throwable $e) {
            return $this->redirect($this->getParameter('front.success_error'));
        }
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
