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

use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class FinalizeResponseContextListener
{
    public function __construct(private readonly ResponseContextAccessor $responseContextAccessor)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->responseContextAccessor->finalizeCurrentContext($event->getResponse());
    }
}
