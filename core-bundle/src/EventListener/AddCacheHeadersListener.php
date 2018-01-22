<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Frontend;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Adds the cache headers to the response according to the page settings.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddCacheHeadersListener
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
     * @var string
     */
    private $fragmentPath;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param ScopeMatcher             $scopeMatcher
     * @param string                   $fragmentPath
     */
    public function __construct(ContaoFrameworkInterface $framework, ScopeMatcher $scopeMatcher, $fragmentPath = '_fragment')
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->fragmentPath = $fragmentPath;
    }

    /**
     * Adds the cache headers to the response according to the page settings.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->framework->isInitialized() || !$this->scopeMatcher->isFrontendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        // Ignore fragments
        if (preg_match('~(?:^|/)'.preg_quote($this->fragmentPath, '~').'/~', $request->getPathInfo())) {
            return;
        }

        // Reset the cache control headers (see symfony/symfony#25699)
        $response = $event->getResponse();
        $response->headers->remove('cache-control');

        /** @var Frontend $frontend */
        $frontend = $this->framework->getAdapter(Frontend::class);
        $frontend->addCacheHeadersToResponse($response);
    }
}
