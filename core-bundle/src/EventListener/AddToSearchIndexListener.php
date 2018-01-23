<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Frontend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Adds a page to the search index after the response has been sent.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AddToSearchIndexListener
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
     * Checks if the request can be indexed and forwards it accordingly.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (!$this->framework->isInitialized() || !$this->scopeMatcher->isFrontendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        // Only index GET requests (see #1194)
        if (!$request->isMethod(Request::METHOD_GET)) {
            return;
        }

        // Do not index fragments
        if (preg_match('~(?:^|/)'.preg_quote($this->fragmentPath, '~').'/~', $request->getPathInfo())) {
            return;
        }

        /** @var Frontend $frontend */
        $frontend = $this->framework->getAdapter(Frontend::class);
        $frontend->indexPageIfApplicable($event->getResponse());
    }
}
