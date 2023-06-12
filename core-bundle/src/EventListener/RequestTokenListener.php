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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Validates the request token if the request is a Contao request.
 *
 * @internal
 */
class RequestTokenListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly string $csrfTokenName,
        private readonly string $csrfCookiePrefix = 'csrf_',
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        // Don't do anything if it's not the main request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only check the request token if a) the request is a POST request, b)
        // the request is not an Ajax request, c) the _token_check attribute is
        // not false, d) the _token_check attribute is set or the request is a
        // Contao request and e) the request has cookies, an authenticated user
        // or the session has been started
        if (
            'POST' !== $request->getRealMethod()
            || $request->isXmlHttpRequest()
            || false === $request->attributes->get('_token_check')
            || $this->csrfTokenManager->canSkipTokenValidation($request, $this->csrfCookiePrefix.$this->csrfTokenName)
            || (!$request->attributes->has('_token_check') && !$this->scopeMatcher->isContaoRequest($request))
        ) {
            return;
        }

        $token = new CsrfToken($this->csrfTokenName, $this->getTokenFromRequest($request));

        if ($this->csrfTokenManager->isTokenValid($token)) {
            return;
        }

        throw new InvalidRequestTokenException('Invalid CSRF token. Please reload the page and try again.');
    }

    private function getTokenFromRequest(Request $request): string|null
    {
        if ($request->request->has('REQUEST_TOKEN')) {
            return (string) $request->request->get('REQUEST_TOKEN');
        }

        // Look for the token inside the root level arrays as they would be in named Symfony forms
        foreach ($request->request as $value) {
            if (\is_array($value) && isset($value['REQUEST_TOKEN'])) {
                return $value['REQUEST_TOKEN'];
            }
        }

        return null;
    }
}
