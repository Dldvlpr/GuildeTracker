<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DiscordController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly ParameterBagInterface $params,
    ) {}

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        // Debug complet
        error_log('=== /api/me DEBUG ===');
        error_log('Method: ' . $request->getMethod());
        error_log('URL: ' . $request->getUri());
        error_log('Origin: ' . $request->headers->get('Origin', 'none'));
        error_log('Host: ' . $request->getHost());
        error_log('Scheme: ' . $request->getScheme());
        error_log('All cookies: ' . json_encode($request->cookies->all()));

        $token = $request->cookies->get('DISCORD_TOKEN');
        error_log('Discord Token: ' . ($token ? 'Present (' . substr($token, 0, 10) . '...)' : 'Missing'));

        if (!$token) {
            error_log('❌ Pas de token DISCORD_TOKEN trouvé');

            // Vérifier les autres cookies au cas où
            $appSession = $request->cookies->get('APP_SESSION');
            $sessionId = $request->cookies->get('PHPSESSID');
            error_log('APP_SESSION: ' . ($appSession ? 'Present' : 'Missing'));
            error_log('PHPSESSID: ' . ($sessionId ? 'Present' : 'Missing'));

            $response = new JsonResponse(['error' => 'Non authentifié - pas de token'], 401);
            $this->addCorsHeaders($response, $request);
            return $response;
        }

        try {
            $client = new Client();
            error_log('🌐 Appel à l\'API Discord avec token: ' . substr($token, 0, 20) . '...');

            $response = $client->get('https://discord.com/api/users/@me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            error_log('✅ Réponse Discord API: ' . json_encode($data));

            $result = new JsonResponse([
                'id' => $data['id'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'avatar' => $data['avatar'] ?? null,
            ]);

            $this->addCorsHeaders($result, $request);
            return $result;

        } catch (\Throwable $e) {
            error_log('❌ Erreur API Discord: ' . $e->getMessage());
            error_log('Code: ' . $e->getCode());

            $result = new JsonResponse([
                'error' => 'Token Discord invalide ou expiré',
                'debug' => $e->getMessage()
            ], 401);

            $this->addCorsHeaders($result, $request);
            return $result;
        }
    }

    #[Route('/connect/discord', name: 'connect_discord_start', methods: ['GET'])]
    public function connectStart(ClientRegistry $clientRegistry, Request $request): RedirectResponse
    {
        error_log('=== /connect/discord START ===');
        error_log('Referer: ' . $request->headers->get('referer', 'none'));

        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email']);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectCheck(Request $request, ClientRegistry $clientRegistry): RedirectResponse
    {
        error_log('=== /connect/discord/check CALLBACK ===');
        error_log('Query params: ' . json_encode($request->query->all()));
        error_log('Request method: ' . $request->getMethod());

        try {
            $client = $clientRegistry->getClient('discord');
            $accessToken = $client->getAccessToken();

            if (!$accessToken) {
                error_log('❌ Pas de token d\'accès reçu');
                throw new \Exception('Pas de token d\'accès reçu');
            }

            error_log('✅ Token reçu: ' . substr($accessToken->getToken(), 0, 20) . '...');

            // Récupérer les infos utilisateur depuis Discord
            $discordUser = $client->fetchUserFromToken($accessToken);
            $discordId = $discordUser->getId();
            $email = $discordUser->getEmail();
            $username = $discordUser->getUsername();

            error_log("Discord User - ID: {$discordId}, Email: {$email}, Username: {$username}");

            // Créer ou mettre à jour l'utilisateur en base
            $user = $this->createOrUpdateUser($discordId, $email, $username);
            error_log("Utilisateur sauvé - DB ID: {$user->getId()}");

            // Créer une réponse de redirection vers le frontend
            $targetUrl = (string) $this->params->get('front.success_uri') . '?status=success';
            error_log("Redirection vers: {$targetUrl}");

            $response = new RedirectResponse($targetUrl);

            // Définir le cookie avec le token Discord
            $isSecure = $request->isSecure();
            error_log("Cookie secure: " . ($isSecure ? 'true' : 'false'));

            $cookie = Cookie::create('DISCORD_TOKEN')
                ->withValue($accessToken->getToken())
                ->withExpires(time() + 86400 * 7) // 7 jours
                ->withPath('/')
                ->withSecure($isSecure)
                ->withHttpOnly(true)
                ->withSameSite($isSecure ? 'None' : 'Lax');

            $response->headers->setCookie($cookie);
            error_log("✅ Cookie DISCORD_TOKEN défini");

            return $response;

        } catch (\Exception $e) {
            error_log('❌ Erreur OAuth Discord: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            $errorUrl = (string) $this->params->get('front.error_uri') . '?status=error&reason=auth_failed';
            return new RedirectResponse($errorUrl);
        }
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['GET', 'POST'])]
    public function logout(Request $request): JsonResponse
    {
        error_log('=== /api/logout ===');
        error_log('Method: ' . $request->getMethod());
        error_log('All cookies: ' . json_encode($request->cookies->all()));

        $response = new JsonResponse(['message' => 'Déconnexion réussie']);

        // Supprimer le cookie Discord
        $response->headers->clearCookie('DISCORD_TOKEN', '/');
        error_log('✅ Cookie DISCORD_TOKEN supprimé');

        $this->addCorsHeaders($response, $request);
        return $response;
    }

    #[Route('/debug/cookies', name: 'debug_cookies', methods: ['GET'])]
    public function debugCookies(Request $request): JsonResponse
    {
        return new JsonResponse([
            'all_cookies' => $request->cookies->all(),
            'discord_token' => $request->cookies->get('DISCORD_TOKEN') ? 'Present' : 'Missing',
            'app_session' => $request->cookies->get('APP_SESSION') ? 'Present' : 'Missing',
            'headers' => $request->headers->all(),
            'url' => $request->getUri(),
        ]);
    }

    private function addCorsHeaders(JsonResponse $response, Request $request): void
    {
        $origin = $request->headers->get('Origin');

        // Permettre localhost avec différents ports pour le développement
        if ($origin && preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:[0-9]+)?$/', $origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            $response->headers->set('Access-Control-Allow-Origin', 'https://localhost:5173');
        }

        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    private function createOrUpdateUser(string $discordId, ?string $email, ?string $username): User
    {
        // Chercher par Discord ID d'abord
        $user = $this->userRepository->findOneBy(['discordId' => $discordId]);

        if ($user) {
            // Mettre à jour les informations si nécessaire
            if ($email && $user->getEmail() !== $email) {
                $user->setEmail($email);
            }
            if ($username && $user->getUsername() !== $username) {
                $user->setUsername($username);
            }
            $this->em->persist($user);
            $this->em->flush();
            return $user;
        }

        // Si pas trouvé par Discord ID, chercher par email
        if ($email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $user->setDiscordId($discordId);
                if ($username) {
                    $user->setUsername($username);
                }
                $this->em->persist($user);
                $this->em->flush();
                return $user;
            }
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setDiscordId($discordId)
            ->setEmail($email)
            ->setUsername($username)
            ->setRoles(['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
