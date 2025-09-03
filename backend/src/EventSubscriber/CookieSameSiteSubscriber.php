<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieSameSiteSubscriber implements EventSubscriberInterface
{
    /**
     * Rewrites the Symfony security cookie created during auth to SameSite=None; Secure
     * to avoid cross-site rejection warnings in modern browsers.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'main_deauth_profile_token') {
                // Remove the original cookie header
                $response->headers->removeCookie(
                    $cookie->getName(),
                    $cookie->getPath(),
                    $cookie->getDomain()
                );

                // Re-add with SameSite=None and Secure=true
                $response->headers->setCookie(new Cookie(
                    name: $cookie->getName(),
                    value: $cookie->getValue(),
                    expire: $cookie->getExpiresTime() ?: 0,
                    path: $cookie->getPath() ?: '/',
                    domain: $cookie->getDomain() ?: null,
                    secure: true,
                    httpOnly: $cookie->isHttpOnly(),
                    raw: false,
                    sameSite: Cookie::SAMESITE_NONE
                ));
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}

