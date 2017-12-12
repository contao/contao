<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @param RouterInterface $router
     * @param ScopeMatcher    $scopeMatcher
     */
    public function __construct(RouterInterface $router, ScopeMatcher $scopeMatcher)
    {
        $this->router = $router;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Redirects the user upon logout.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function onLogoutSuccess(Request $request): RedirectResponse
    {
        $session = $request->getSession();

        if ($session && $session->has('_contao_logout_target')) {
            return new RedirectResponse($session->get('_contao_logout_target'));
        }

        if ($this->scopeMatcher->isBackendRequest($request)) {
            return new RedirectResponse($this->router->generate('contao_backend_login'));
        }

        return new RedirectResponse($this->router->generate('contao_root'));
    }
}
