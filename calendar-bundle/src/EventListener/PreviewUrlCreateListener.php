<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\DataContainer\DynamicPtableTrait;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 */
#[AsEventListener]
class PreviewUrlCreateListener
{
    use DynamicPtableTrait;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly DcaUrlAnalyzer $dcaUrlAnalyzer,
        private readonly Connection $connection,
    ) {
    }

    /**
     * Adds the calendar ID to the front end preview URL.
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || 'calendar' !== $event->getKey()) {
            return;
        }

        [$table, $id] = $this->dcaUrlAnalyzer->getCurrentTableId();

        // Return on the calendar list page
        if (null === $id || !\in_array($table, ['tl_calendar_events', 'tl_content'], true)) {
            return;
        }

        if ('tl_content' === $table) {
            [$table, $id] = $this->getParentTableAndId($this->connection, $table, $id);
        }

        if ('tl_calendar_events' !== $table) {
            return;
        }

        $event->setQuery('calendar='.$id);
    }
}
