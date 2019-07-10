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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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

        if ($this->requiresCsrf($request, $response)) {
            $this->setCookies($request, $response);
        } else {
            $this->removeCookies($request, $response);
        }
    }

    private function requiresCsrf(Request $request, Response $response): bool
    {
        foreach ($request->cookies as $key => $value) {
            if (!$this->isCsrfCookie($key, $value)) {
                return true;
            }
        }

        if (\count($response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY))) {
            return true;
        }

        if ($request->getUserInfo()) {
            return true;
        }

        if ($request->hasSession() && $request->getSession()->isStarted()) {
            return true;
        }

        return false;
    }

    private function setCookies(Request $request, Response $response): void
    {
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

    private function removeCookies(Request $request, Response $response): void
    {
        $isSecure = $request->isSecure();
        $basePath = $request->getBasePath() ?: '/';

        foreach ($request->cookies as $key => $value) {
            if ($this->isCsrfCookie($key, $value)) {
                $response->headers->clearCookie($key, $basePath, null, $isSecure);
            }
        }
    }

    /**
     * @return array<string,string>
     */
    private function getTokensFromCookies(ParameterBag $cookies): array
    {
        $tokens = [];

        foreach ($cookies as $key => $value) {
            if ($this->isCsrfCookie($key, $value)) {
                $tokens[substr($key, \strlen($this->cookiePrefix))] = $value;
            }
        }

        return $tokens;
    }

    private function isCsrfCookie($key, string $value): bool
    {
        if (!\is_string($key)) {
            return false;
        }

        return 0 === strpos($key, $this->cookiePrefix) && preg_match('/^[a-z0-9_-]+$/i', $value);
    }
}
