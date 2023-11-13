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
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class FilterPageTypeListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(FilterPageTypeEvent $event): void
    {
        $dc = $event->getDataContainer();
        $currentRecord = $dc->getCurrentRecord();

        if (null === $currentRecord) {
            return;
        }

        // The first level can only have root pages (see #6360)
        if (!($currentRecord['pid'] ?? null)) {
            $event->setOptions(['root']);

            return;
        }

        $event->removeOption('root');

        $parentType = $this->connection->fetchOne('SELECT type FROM tl_page WHERE id=?', [$currentRecord['pid'] ?? null]);

        // Error pages can only be placed directly inside root pages
        if ('root' !== $parentType) {
            $event->removeOption('error_401');
            $event->removeOption('error_403');
            $event->removeOption('error_404');
            $event->removeOption('error_503');

            return;
        }

        $siblingTypes = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT(type) FROM tl_page WHERE pid=? AND id!=?',
            [$currentRecord['pid'] ?? null, $currentRecord['id'] ?? null],
        );

        foreach (array_intersect(['error_401', 'error_403', 'error_404', 'error_503'], $siblingTypes) as $type) {
            $event->removeOption($type);
        }
    }
}
