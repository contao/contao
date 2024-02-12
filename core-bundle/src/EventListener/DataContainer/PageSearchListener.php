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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Search;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class PageSearchListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback(table: 'tl_page', target: 'fields.alias.save')]
    public function onSaveAlias(string $value, DataContainer $dc): string
    {
        if ($value === ($dc->getCurrentRecord()['alias'] ?? null)) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_page', target: 'fields.noSearch.save')]
    public function onSaveNoSearch(string $value, DataContainer $dc): string
    {
        if (!$value || (bool) $value === (bool) ($dc->getCurrentRecord()['noSearch'] ?? false)) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_page', target: 'fields.robots.save')]
    public function onSaveRobots(string $value, DataContainer $dc): string
    {
        if ($value === ($dc->getCurrentRecord()['robots'] ?? null) || !str_starts_with($value, 'noindex')) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_page', target: 'config.ondelete', priority: 16)]
    public function onDelete(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $this->purgeSearchIndex((int) $dc->id);
    }

    private function purgeSearchIndex(int $pageId): void
    {
        $urls = $this->connection->fetchFirstColumn(
            'SELECT url FROM tl_search WHERE pid=:pageId',
            ['pageId' => $pageId],
        );

        $search = $this->framework->getAdapter(Search::class);

        foreach ($urls as $url) {
            $search->removeEntry($url);
        }
    }
}
