<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Csrf;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class ContaoCsrfTokenManager extends CsrfTokenManager
{
    private RequestStack $requestStack;
    private string $csrfCookiePrefix;

    public function __construct(RequestStack $requestStack, string $csrfCookiePrefix, TokenGeneratorInterface $generator = null, TokenStorageInterface $storage = null, $namespace = null)
    {
        $this->requestStack = $requestStack;
        $this->csrfCookiePrefix = $csrfCookiePrefix;

        parent::__construct($generator, $storage, $namespace);
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        if (
            ($request = $this->requestStack->getMasterRequest())
            && 'POST' === $request->getRealMethod()
            && $this->canSkipTokenValidation($request, $this->csrfCookiePrefix.$token->getId())
        ) {
            return true;
        }

        return parent::isTokenValid($token);
    }

    /**
     * Skip the CSRF token validation if the request has no cookies, no
     * authenticated user and the session has not been started.
     */
    public function canSkipTokenValidation(Request $request, string $tokenCookieName): bool
    {
        return
            !$request->getUserInfo()
            && (
                0 === $request->cookies->count()
                || [$tokenCookieName] === $request->cookies->keys()
            )
            && !($request->hasSession() && $request->getSession()->isStarted())
        ;
    }
}
