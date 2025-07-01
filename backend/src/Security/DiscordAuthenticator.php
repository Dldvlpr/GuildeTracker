<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
        private readonly UserRepository $userRepository
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_discord_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('discord');
        $accessToken = $this->fetchAccessToken($client);

        /** @var DiscordResourceOwner $discordUser */
        $discordUser = $client->fetchUserFromToken($accessToken);

        $discordId = $discordUser->getId();
        $email     = $discordUser->getEmail();
        $username  = $discordUser->getUsername();

        return new SelfValidatingPassport(
            new UserBadge($discordId, function() use ($discordId, $email, $username) {
                $existing = $this->userRepository->findOneBy(['discordId' => $discordId]);
                if ($existing) {
                    return $existing;
                }
                if ($email) {
                    $existing = $this->userRepository->findOneBy(['email' => $email]);
                }
                if (isset($existing)) {
                    $existing->setDiscordId($discordId);
                    if ($username) {
                        $existing->setUsername($username);
                    }
                    $this->em->persist($existing);
                    $this->em->flush();
                    return $existing;
                }
                $user = new User();
                $user->setDiscordId($discordId)
                    ->setEmail($email)
                    ->setUsername($username)
                    ->setRoles(['ROLE_USER']);
                $this->em->persist($user);
                $this->em->flush();
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->getTargetPath($request->getSession(), $firewallName);
        if (!$targetUrl) {
            $targetUrl = 'https://localhost:5173';
        }
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new Response("Échec de l'authentification : $message", Response::HTTP_FORBIDDEN);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $loginUrl = $this->router->generate('connect_discord_start');
        return new RedirectResponse($loginUrl, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
