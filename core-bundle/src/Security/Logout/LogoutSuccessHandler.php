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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var JwtManager
     */
    private $jwtManager;

    public function __construct(HttpUtils $httpUtils, ScopeMatcher $scopeMatcher, JwtManager $jwtManager = null)
    {
        parent::__construct($httpUtils);

        $this->scopeMatcher = $scopeMatcher;
        $this->jwtManager = $jwtManager;
    }

    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request): Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->createRedirectResponse($request, 'contao_backend_login');
        }

        if ($targetUrl = $request->query->get('redirect')) {
            return $this->createRedirectResponse($request, $targetUrl);
        }

        if ($targetUrl = $request->headers->get('Referer')) {
            return $this->createRedirectResponse($request, $targetUrl);
        }

        return $this->clearJwtToken(parent::onLogoutSuccess($request));
    }

    private function createRedirectResponse(Request $request, string $targetUrl): Response
    {
        $response = $this->httpUtils->createRedirectResponse($request, $targetUrl);

        return $this->clearJwtToken($response);
    }

    private function clearJwtToken(Response $response): Response
    {
        if (null !== $this->jwtManager) {
            return $this->jwtManager->clearResponseCookie($response);
        }

        return $response;
    }
}
