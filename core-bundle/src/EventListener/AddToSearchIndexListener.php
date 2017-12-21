<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Frontend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

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
     * @param ContaoFrameworkInterface $framework
     * @param string                   $fragmentPath
     */
    public function __construct(ContaoFrameworkInterface $framework, string $fragmentPath = '_fragment')
    {
        $this->framework = $framework;
        $this->fragmentPath = $fragmentPath;
    }

    /**
     * Forwards the request to the Frontend class if there is a page object.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
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
