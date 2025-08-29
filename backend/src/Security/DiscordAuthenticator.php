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
        try {
            $client = $this->clientRegistry->getClient('discord');
            $accessToken = $this->fetchAccessToken($client);

            $request->getSession()->set('discord_access_token', $accessToken->getToken());

            /** @var DiscordResourceOwner $discordUser */
            $discordUser = $client->fetchUserFromToken($accessToken);

            $discordId = $discordUser->getId();
            $email     = $discordUser->getEmail();
            $username  = $discordUser->getUsername();

            error_log("Discord Auth - ID: {$discordId}, Email: {$email}, Username: {$username}");

            return new SelfValidatingPassport(
                new UserBadge($discordId, function() use ($discordId, $email, $username) {
                    $existing = $this->userRepository->findOneBy(['discordId' => $discordId]);
                    if ($existing) {
                        error_log("Utilisateur existant trouvé par Discord ID: {$existing->getId()}");
                        return $existing;
                    }

                    if ($email) {
                        $existing = $this->userRepository->findOneBy(['email' => $email]);
                        if ($existing) {
                            $existing->setDiscordId($discordId);
                            if ($username) {
                                $existing->setUsername($username);
                            }
                            $this->em->persist($existing);
                            $this->em->flush();
                            error_log("Utilisateur existant mis à jour avec Discord ID: {$existing->getId()}");
                            return $existing;
                        }
                    }

                    $user = new User();
                    $user->setDiscordId($discordId)
                        ->setEmail($email)
                        ->setUsername($username)
                        ->setRoles(['ROLE_USER']);

                    $this->em->persist($user);
                    $this->em->flush();

                    error_log("Nouvel utilisateur créé: {$user->getId()}");
                    return $user;
                })
            );
        } catch (\Exception $e) {
            error_log("Erreur dans l'authentification Discord: " . $e->getMessage());
            throw new AuthenticationException('Erreur lors de l\'authentification Discord: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->getTargetPath($request->getSession(), $firewallName);
        if (!$targetUrl) {
            $targetUrl = 'https://localhost:5173';
        }

        $accessToken = $request->getSession()->get('discord_access_token');

        if (!$accessToken) {
            error_log("Aucun token OAuth Discord trouvé dans la session");
            return new Response("Aucun token OAuth Discord trouvé", Response::HTTP_UNAUTHORIZED);
        }

        $response = new RedirectResponse($targetUrl);

        $response->headers->setCookie(
            new \Symfony\Component\HttpFoundation\Cookie(
                name: 'DISCORD_TOKEN',
                value: $accessToken,
                expire: time() + 3600,
                path: '/',
                domain: null,
                secure: true,
                httpOnly: true,
                sameSite: 'Lax'
            )
        );

        error_log("Authentification réussie, redirection vers: {$targetUrl}");
        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        error_log("Échec de l'authentification Discord: {$message}");

        $errorUrl = 'https://localhost:5173?error=auth_failed&message=' . urlencode($message);
        return new RedirectResponse($errorUrl);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $loginUrl = $this->router->generate('connect_discord_start');
        return new RedirectResponse($loginUrl, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
