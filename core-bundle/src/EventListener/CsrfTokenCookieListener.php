<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CsrfTokenCookieListener
{
    /**
     * @var MemoryTokenStorage
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $cookiePrefix;

    public function __construct(MemoryTokenStorage $tokenStorage, string $cookiePrefix = 'csrf_')
    {
        $this->tokenStorage = $tokenStorage;
        $this->cookiePrefix = $cookiePrefix;
    }

    /**
     * Reads the cookies from the request and injects them into the storage.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->tokenStorage->initialize($this->getTokensFromCookies($event->getRequest()->cookies));
    }

    /**
     * Adds the token cookies to the response.
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $isSecure = $request->isSecure();
        $basePath = $request->getBasePath() ?: '/';

        foreach ($this->tokenStorage->getUsedTokens() as $key => $value) {
            $cookieKey = $this->cookiePrefix.$key;

            // The cookie already exists
            if ($request->cookies->has($cookieKey) && $value === $request->cookies->get($cookieKey)) {
                continue;
            }

            $expires = null === $value ? 1 : 0;

            $response->headers->setCookie(
                new Cookie($cookieKey, $value, $expires, $basePath, null, $isSecure, true, false, Cookie::SAMESITE_LAX)
            );
        }
    }

    /**
     * @return array<string,string>
     */
    private function getTokensFromCookies(ParameterBag $cookies): array
    {
        $tokens = [];

        foreach ($cookies as $key => $value) {
            if (!\is_string($key)) {
                continue;
            }

            if (0 === strpos($key, $this->cookiePrefix) && preg_match('/^[a-z0-9_-]+$/i', $value)) {
                $tokens[substr($key, \strlen($this->cookiePrefix))] = $value;
            }
        }

        return $tokens;
    }
}
