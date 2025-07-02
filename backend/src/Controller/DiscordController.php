<?php

namespace App\Controller;

use App\Repository\UserRepository;
use GuzzleHttp\Client;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email']);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectCheck(): RedirectResponse
    {
        return $this->redirect('https://discordapp.com/api/oauth2/token', 302);
    }
}
