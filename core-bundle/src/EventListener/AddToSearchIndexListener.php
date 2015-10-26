<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
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
 *
 * @internal
 */
class AddToSearchIndexListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework The Contao framework service
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Forwards the request to the Frontend class if there is a page object.
     *
     * @param PostResponseEvent $event The event object
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        /** @var Frontend $frontend */
        $frontend = $this->framework->getAdapter('Contao\Frontend');
        $frontend->indexPageIfApplicable($event->getResponse());
    }
}
