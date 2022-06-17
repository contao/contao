<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version500;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class RssToFeedReaderMigration extends AbstractMigration
{
    public function __construct(private Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('tl_module');

        return \array_key_exists('rss_feed', $columns) || \array_key_exists('rss_cache', $columns);
    }

    public function run(): MigrationResult
    {
        $this->renameField(
            $this->connection->quoteIdentifier('rss_feed'),
            $this->connection->quoteIdentifier('feed_urls'),
            'text NULL'
        );

        $this->renameField(
            $this->connection->quoteIdentifier('rss_cache'),
            $this->connection->quoteIdentifier('feed_cache'),
            'int(10) unsigned NOT NULL default 3600'
        );

        $this->connection->update('tl_module', ['type' => 'feed_reader'], ['type' => 'rssReader']);

        return $this->createResult(true);
    }

    private function renameField(string $fromQuoted, string $toQuoted, string $type): void
    {
        $tableQuoted = $this->connection->quoteIdentifier('tl_module');
        $this->connection->executeQuery("ALTER TABLE $tableQuoted CHANGE $fromQuoted $toQuoted $type");
    }
}
