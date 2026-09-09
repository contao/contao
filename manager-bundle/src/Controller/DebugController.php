<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Controller;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;

class DebugController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly JwtManager $jwtManager,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly string $csrfTokenName,
    ) {
    }

    public function enableAction(): RedirectResponse
    {
        return $this->updateJwtCookie(true);
    }

    public function disableAction(): RedirectResponse
    {
        return $this->updateJwtCookie(false);
    }

    private function updateJwtCookie(bool $debug): RedirectResponse
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenName, $request->query->get('rt')))) {
            throw new InvalidRequestTokenException('Invalid CSRF token. Please reload the page and try again.');
        }

        $referer = '';

        if ($request->query->has('referer')) {
            $referer = '?'.base64_decode($request->query->get('referer'), true);
        }

        $response = new RedirectResponse($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$referer);

        $this->jwtManager->addResponseCookie($response, ['debug' => $debug]);

        return $response;
    }
}
