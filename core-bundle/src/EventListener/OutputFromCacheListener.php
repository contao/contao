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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Outputs a page from cache without loading a controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @internal
 */
class OutputFromCacheListener
{
    use ScopeAwareTrait;

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
     * Forwards the request to the Frontend class and sets the response if any.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->isFrontendMasterRequest($event)) {
            return;
        }

        $this->framework->initialize();

        if (null !== ($response = Frontend::getResponseFromCache())) {
            $event->setResponse($response);
        }
    }
}
