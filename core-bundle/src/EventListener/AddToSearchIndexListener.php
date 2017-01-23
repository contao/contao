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
use Contao\Frontend;
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
     * @var string
     */
    private $fragmentPath;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param string                   $fragmentPath
     */
    public function __construct(ContaoFrameworkInterface $framework, $fragmentPath = '_fragment')
    {
        $this->framework = $framework;
        $this->fragmentPath = $fragmentPath;
    }

    /**
     * Forwards the request to the Frontend class if there is a page object.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        // Do not index fragments
        if (preg_match('~(?:^|/)'.preg_quote($this->fragmentPath, '~').'/~', $event->getRequest()->getPathInfo())) {
            return;
        }

        /** @var Frontend $frontend */
        $frontend = $this->framework->getAdapter(Frontend::class);
        $frontend->indexPageIfApplicable($event->getResponse());
    }
}
