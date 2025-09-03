<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient) {}

    #[Route('/api/login', name: 'discord_login')]
    public function login(): RedirectResponse
    {
        $params = http_build_query([
            'client_id'     => $_ENV['OAUTH_DISCORD_CLIENT_ID'],
            'redirect_uri'  => $_ENV['OAUTH_DISCORD_REDIRECT'],
            'response_type' => 'code',
            'scope'         => 'identify email',
        ]);

        return new RedirectResponse("https://discord.com/api/oauth2/authorize?$params");
    }

    #[Route('/api/callback', name: 'discord_callback')]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Code manquant', 400);
        }

        $response = $this->httpClient->request('POST', 'https://discord.com/api/oauth2/token', [
            'body' => [
                'client_id'     => $_ENV['OAUTH_DISCORD_CLIENT_ID'],
                'client_secret' => $_ENV['OAUTH_DISCORD_CLIENT_SECRET'],
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $_ENV['OAUTH_DISCORD_REDIRECT'],
            ],
        ]);

        $data = $response->toArray();

        $userResponse = $this->httpClient->request('GET', 'https://discord.com/api/users/@me', [
            'headers' => [
                'Authorization' => 'Bearer '.$data['access_token'],
            ],
        ]);

        $user = $userResponse->toArray();
        $request->getSession()->set('user', $user);

        return $this->redirect($_ENV['FRONT_SUCCESS_URI']);
    }
}
