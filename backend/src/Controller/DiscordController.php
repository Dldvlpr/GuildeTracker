<?php

namespace App\Controller;

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

        return $clientRegistry->getClient('discord')->redirect(['identify', 'email']);
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

