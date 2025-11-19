<?php

namespace App\Controller;

use App\Security\OAuthSessionKeys;
use App\Security\ReturnToSanitizer;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

final class DiscordController extends AbstractController
{
    #[Route('/connect/discord', name: 'connect_discord_start', methods: ['GET'])]
    public function connect(ClientRegistry $clientRegistry, Request $request, RateLimiterFactory $discordOauthStartLimiter): RedirectResponse
    {
        $limiter = $discordOauthStartLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            throw $this->createAccessDeniedException('Too many requests');
        }

        $returnTo = ReturnToSanitizer::sanitize($request->query->get('returnTo'));
        if ($returnTo) {
            $request->getSession()->set(OAuthSessionKeys::DISCORD_REDIRECT, $returnTo);
        } else {
            $request->getSession()->remove(OAuthSessionKeys::DISCORD_REDIRECT);
        }

        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $request->getSession()->set('discord_pkce_verifier', $verifier);

        return $clientRegistry->getClient('discord')->redirect(
            ['identify', 'email'],
            [
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]
        );
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check', methods: ['GET'])]
    public function check(Request $request, RateLimiterFactory $discordOauthCallbackLimiter): void
    {
        $limiter = $discordOauthCallbackLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            throw $this->createAccessDeniedException('Too many requests');
        }
    }


    #[Route('/logout', name: 'api_logout', methods: ['GET', 'POST'])]
    public function logout(): void
    {
        throw new \LogicException('This method should never be reached!');
    }
}
