<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\TwoFactor;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class FormRenderer implements TwoFactorFormRendererInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    public function __construct(RouterInterface $router, ScopeMatcher $scopeMatcher)
    {
        $this->router = $router;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function renderForm(Request $request, array $templateVars): Response
    {
        return new RedirectResponse($this->router->generate($this->getRedirectRoute($request)));
    }

    private function getRedirectRoute(Request $request)
    {
        if ($this->scopeMatcher->isFrontendRequest($request)) {
            return 'contao_frontend_two_factor';
        }

        if ($this->scopeMatcher->isBackendRequest($request)) {
            return 'contao_backend_two_factor';
        }

        throw new \RuntimeException('Invalid scrope');
    }
}
