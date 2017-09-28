<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
     * @var int
     */
    private $cookieLifetime;

    /**
     * @var string
     */
    private $cookiePrefix;

    /**
     * @param MemoryTokenStorage $tokenStorage
     * @param int                $cookieLifetime
     * @param string             $cookiePrefix
     */
    public function __construct(MemoryTokenStorage $tokenStorage, int $cookieLifetime = 86400, string $cookiePrefix = 'csrf_')
    {
        $this->tokenStorage = $tokenStorage;
        $this->cookieLifetime = $cookieLifetime;
        $this->cookiePrefix = $cookiePrefix;
    }

    /**
     * Reads the cookies from the request and injects them into the storage.
     *
     * @param GetResponseEvent $event
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
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $cookieLifetime = $this->cookieLifetime ? $this->cookieLifetime + time() : 0;
        $isSecure = $request->isSecure();
        $basePath = $request->getBasePath() ?: '/';

        foreach ($this->tokenStorage->getUsedTokens() as $key => $value) {
            $response->headers->setCookie(
                new Cookie(
                    $this->cookiePrefix.$key,
                    $value,
                    null === $value ? 1 : $cookieLifetime,
                    $basePath,
                    null,
                    $isSecure,
                    true,
                    false,
                    Cookie::SAMESITE_LAX
                )
            );
        }
    }

    /**
     * Returns the tokens from the cookies.
     *
     * @param ParameterBag $cookies
     *
     * @return array
     */
    private function getTokensFromCookies(ParameterBag $cookies): array
    {
        $tokens = [];

        foreach ($cookies as $key => $value) {
            if (0 === strncmp($key, $this->cookiePrefix, \strlen($this->cookiePrefix))) {
                $tokens[substr($key, \strlen($this->cookiePrefix))] = $value;
            }
        }

        return $tokens;
    }
}
