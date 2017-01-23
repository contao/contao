<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\System;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Sets the TL_VIEW cookie based on the "toggle_view" query parameter.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ToggleViewListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param ScopeMatcher             $scopeMatcher
     */
    public function __construct(ContaoFrameworkInterface $framework, ScopeMatcher $scopeMatcher)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Toggles the TL_VIEW cookie and redirects back to the referring page.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
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
     *
     * @param string $value
     * @param string $basePath
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
