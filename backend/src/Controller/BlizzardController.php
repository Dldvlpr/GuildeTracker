<?php

namespace App\Controller;

use App\Entity\User;
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
        } catch (\Throwable $e) {
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
}

