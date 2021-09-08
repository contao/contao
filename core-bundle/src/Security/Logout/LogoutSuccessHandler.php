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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    private ScopeMatcher $scopeMatcher;

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.logout_success_handler" service instead
     */
    public function __construct(HttpUtils $httpUtils, ScopeMatcher $scopeMatcher)
    {
        parent::__construct($httpUtils);

        $this->scopeMatcher = $scopeMatcher;
    }

    public function onLogoutSuccess(Request $request): Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->httpUtils->createRedirectResponse($request, 'contao_backend_login');
        }

        if ($targetUrl = $request->request->get('_target_path')) {
            return $this->httpUtils->createRedirectResponse($request, $targetUrl);
        }

        if ($targetUrl = $request->query->get('redirect')) {
            return $this->httpUtils->createRedirectResponse($request, $targetUrl);
        }

        if ($targetUrl = $request->headers->get('Referer')) {
            return $this->httpUtils->createRedirectResponse($request, $targetUrl);
        }

        return parent::onLogoutSuccess($request);
    }
}
