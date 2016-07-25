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
use Contao\CoreBundle\Framework\ScopeAwareTrait;
use Contao\Frontend;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Outputs a page from cache without loading a controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
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
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Forwards the request to the Frontend class and sets the response if any.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->isFrontendMasterRequest($event)) {
            return;
        }

        $this->framework->initialize();

        /** @var Frontend $frontend */
        $frontend = $this->framework->getAdapter('Contao\Frontend');

        if (null !== ($response = $frontend->getResponseFromCache())) {
            $event->setResponse($response);
        }
    }
}
