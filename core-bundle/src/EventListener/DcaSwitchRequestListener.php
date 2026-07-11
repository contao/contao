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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaLoader;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 */
class DcaSwitchRequestListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $framework,
    ) {
    }

    #[AsEventListener]
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->framework->isInitialized()) {
            $this->framework->getAdapter(DcaLoader::class)->switchToCurrentRequest();
        }
    }

    #[AsEventListener]
    public function onKernelFinishRequestEvent(FinishRequestEvent $event): void
    {
        if (!$parentRequest = $this->requestStack->getParentRequest()) {
            return;
        }

        if ($this->framework->isInitialized()) {
            $this->framework->getAdapter(DcaLoader::class)->switchToCurrentRequest($parentRequest);
        }
    }
}
