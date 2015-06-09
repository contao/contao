<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\ContaoFramework;
use Contao\System;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Sets the TL_VIEW cookie based on the "toggle_view" query parameter.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @internal
 */
class ToggleViewListener
{
    use ScopeAwareTrait;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ContaoFramework $framework The Contao framework service
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Toggles the TL_VIEW cookie and redirects back to the referring page.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->isFrontendMasterRequest($event) || !$request->query->has('toggle_view')) {
            return;
        }

        $this->framework->initialize();

        $response = new RedirectResponse(System::getReferer(), 303);
        $response->headers->setCookie($this->getCookie($request->query->get('toggle_view'), $request->getBasePath()));

        $event->setResponse($response);
    }

    /**
     * Generates the TL_VIEW cookie based on the toggle_view value.
     *
     * @param string $value    The cookie value
     * @param string $basePath The request base path
     *
     * @return Cookie The cookie object
     */
    private function getCookie($value, $basePath)
    {
        if ('mobile' !== $value) {
            $value = 'desktop';
        }

        return new Cookie('TL_VIEW', $value, 0, $basePath);
    }
}
