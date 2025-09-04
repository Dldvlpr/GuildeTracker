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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
        private readonly UserRepository $userRepository,
        private readonly ParameterBagInterface $params,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_discord_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        try {
            $client = $this->clientRegistry->getClient('discord');
            $verifier = $request->getSession()->get('discord_pkce_verifier');
            $request->getSession()->remove('discord_pkce_verifier');
            $options = $verifier ? ['code_verifier' => $verifier] : [];
            $accessToken = $this->fetchAccessToken($client, $options);

            $request->getSession()->set('discord_access_token', $accessToken->getToken());

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
                        if ($existing) {
                            $existing->setDiscordId($discordId);
                            if ($username) {
                                $existing->setUsername($username);
                            }
                            $this->em->persist($existing);
                            $this->em->flush();
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

                    return $user;
                })
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Erreur on auth: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->getTargetPath($request->getSession(), $firewallName) ??
            (string) $this->params->get('front.success_uri');

        $accessToken = $request->getSession()->get('discord_access_token');
        if (!$accessToken) {
            return new Response("Token not found", Response::HTTP_UNAUTHORIZED);
        }

        $response = new RedirectResponse($targetUrl);

        $isSecure = $request->isSecure();

        $user = $token->getUser();
        if ($user instanceof \App\Entity\User) {
            $cookieValue = base64_encode(json_encode(['uid' => $user->getId()]));

            $cookie = Cookie::create('APP_SESSION')
                ->withValue($cookieValue)
                ->withPath('/')
                ->withSecure($isSecure)
                ->withHttpOnly(true)
                ->withSameSite($isSecure ? 'None' : 'Lax');

            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getMessage();
        error_log("Échec de l'authentification Discord: {$message}");

        $errorUrl = (string) $this->params->get('front.error_uri');
        $glue = str_contains($errorUrl, '?') ? '&' : '?';
        $errorUrl .= $glue . 'reason=auth_failed&message=' . urlencode($message);
        return new RedirectResponse($errorUrl);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $loginUrl = $this->router->generate('connect_discord_start');
        return new RedirectResponse($loginUrl, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
