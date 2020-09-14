<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Response\InitializeControllerResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @internal
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0
 */
class InitializeControllerListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if ($event->getResponse() instanceof InitializeControllerResponse) {
            $event->stopPropagation();
        }
    }
}
