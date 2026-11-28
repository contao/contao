<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Removes the search index tables and maintenance tasks when the search
 * index is disabled.
 *
 * @internal
 */
class DisableSearchIndexListener
{
    public function __construct(private readonly ScopeMatcher $scopeMatcher)
    {
    }

    #[AsHook('loadDataContainer', priority: -128)]
    public function onLoadDataContainer(): void
    {
        unset(
            $GLOBALS['TL_DCA']['tl_search'],
            $GLOBALS['TL_DCA']['tl_search_index'],
            $GLOBALS['TL_DCA']['tl_search_term'],
        );
    }

    #[AsEventListener(priority: -128)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isBackendRequest($event->getRequest())) {
            return;
        }

        unset($GLOBALS['TL_PURGE']['tables']['index']);
    }
}
