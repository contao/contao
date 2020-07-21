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

use Contao\CoreBundle\Event\FilterPageTypeEvent;

/**
 * @internal
 */
class FilterPageTypeListener
{
    public function __invoke(FilterPageTypeEvent $event): void
    {
        $dc = $event->getDataContainer();

        if (!$dc->activeRecord) {
            return;
        }

        // Root pages are allowed on the first level only (see #6360)
        if ($dc->activeRecord->pid > 0) {
            $event->removeOption('root');
        } else {
            $event->setOptions(['root']);
        }
    }
}
