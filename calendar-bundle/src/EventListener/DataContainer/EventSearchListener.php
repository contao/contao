<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener\DataContainer;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\DataContainer;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class EventSearchListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'fields.alias.save')]
    public function onSaveAlias(string $value, DataContainer $dc): string
    {
        if (($dc->getCurrentRecord()['alias'] ?? null) === $value) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'fields.searchIndexer.save')]
    public function onSaveSearchIndexer(string $value, DataContainer $dc): string
    {
        if (!$value || 'always_index' === $value || $value === ($dc->getCurrentRecord()['searchIndexer'] ?? null)) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'fields.robots.save')]
    public function onSaveRobots(string $value, DataContainer $dc): string
    {
        if (($dc->getCurrentRecord()['robots'] ?? null) === $value || str_starts_with($value, 'index') || (str_starts_with($value, 'noindex') && str_starts_with($dc->getCurrentRecord()['searchIndexer'], 'always'))) {
            return $value;
        }

        if ('' === $value) {
            // Get robots and searchIndexer of the reader page (linked in calendar)
            $readerPageRobots = $this->connection->fetchAssociative(
                <<<'SQL'
                    SELECT p.robots, p.searchIndexer
                    FROM tl_page AS p, tl_calendar AS c
                    WHERE c.id = ? AND c.jumpTo = p.id
                    SQL,
                [$dc->getCurrentRecord()['pid']],
            );

            if (str_starts_with((string) $readerPageRobots['robots'], 'index') || str_starts_with($readerPageRobots['searchIndexer'], 'always')) {
                return $value;
            }
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'config.ondelete', priority: 16)]
    public function onDelete(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $this->purgeSearchIndex((int) $dc->id);
    }

    private function purgeSearchIndex(int $eventId): void
    {
        $event = $this->framework->getAdapter(CalendarEventsModel::class)->findById($eventId);

        try {
            $eventUrl = $this->urlGenerator->generate($event, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (ExceptionInterface) {
            return;
        }

        $search = $this->framework->getAdapter(Search::class);
        $search->removeEntry($eventUrl);
    }
}
