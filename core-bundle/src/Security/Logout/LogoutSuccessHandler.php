<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Logout;

use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request): Response
    {
        // TODO only handle backend?

        if ($targetUrl = $request->query->get('redirect')) {
            return $this->createRedirectResponse($request, $targetUrl);
        }

        if ($targetUrl = $request->headers->get('Referer')) {
            return $this->createRedirectResponse($request, $targetUrl);
        }

        return $this->clearJwtToken($request, parent::onLogoutSuccess($request));
    }

    private function createRedirectResponse(Request $request, string $targetUrl)
    {
        $response = $this->httpUtils->createRedirectResponse($request, $targetUrl);

        return $this->clearJwtToken($request, $response);
    }

    private function clearJwtToken(Request $request, Response $response)
    {
        $jwtManager = $request->attributes->get(JwtManager::ATTRIBUTE);

        if ($jwtManager instanceof JwtManager) {
            return $jwtManager->clearResponseCookie($response);
        }

        return $response;
    }
}
