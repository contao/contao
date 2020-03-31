<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Response\InitializeControllerResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Response event listener to support legacy entry point scripts.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0
 */
class InitializeControllerListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getResponse() instanceof InitializeControllerResponse) {
            $event->stopPropagation();
        }
    }
}
