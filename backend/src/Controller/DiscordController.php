<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;

class DiscordController extends AbstractController
{
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email']);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectCheck(Request $request): RedirectResponse
    {
        return $this->redirect('https://discordapp.com/api/oauth2/token', 302);
    }
}
