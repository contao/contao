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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\System;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ToggleViewListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Toggles the TL_VIEW cookie and redirects back to the referring page.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->scopeMatcher->isFrontendMasterRequest($event) || !$request->query->has('toggle_view')) {
            return;
        }

        $this->framework->initialize();

        $response = new RedirectResponse(System::getReferer(), 303);
        $response->headers->setCookie($this->getCookie($request->query->get('toggle_view'), $request->getBasePath()));

        $event->setResponse($response);
    }

    /**
     * Generates the TL_VIEW cookie based on the toggle_view value.
     */
    private function getCookie(string $value, string $basePath): Cookie
    {
        if ('mobile' !== $value) {
            $value = 'desktop';
        }

        return new Cookie('TL_VIEW', $value, 0, $basePath);
    }
}
