<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class FeedMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection, private readonly LoggerInterface $logger)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_news_feed'])) {
            return false;
        }

        return $this->connection->fetchOne('SELECT COUNT(*) FROM tl_news_feed') > 0;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = array_keys($schemaManager->listTableColumns('tl_page'));

        $newFields = [
            'newsArchives' => 'blob NULL',
            'feedFormat' => "varchar(32) NOT NULL default 'rss'",
            'feedSource' => "varchar(32) NOT NULL default 'source_teaser'",
            'maxFeedItems' => 'smallint(5) unsigned NOT NULL default 25',
            'feedFeatured' => "varchar(16) COLLATE ascii_bin NOT NULL default 'all_items'",
            'imgSize' => "varchar(255) NOT NULL default ''",
        ];

        foreach ($newFields as $field => $definition) {
            if (\in_array(strtolower($field), $columns, true)) {
                continue;
            }

            $this->connection->executeStatement("ALTER TABLE tl_page ADD $field $definition");
        }

        // Migrate data from `tl_news_feeds` to `tl_page`
        $feeds = $this->connection->fetchAllAssociative('SELECT * FROM tl_news_feed');

        foreach ($feeds as $feed) {
            $rootPage = $this->findMatchingRootPage($feed);

            if (!$rootPage) {
                $this->logger->warning('Could not migrate feed "'.$feed['title'].'" because there is no root page');
                continue;
            }

            $this->connection->insert('tl_page', [
                'type' => 'news_feed',
                'pid' => $rootPage,
                'tstamp' => $feed['tstamp'],
                'title' => $feed['title'],
                'alias' => 'share/'.$feed['alias'],
                'description' => $feed['description'],
                'feedSource' => $feed['source'],
                'feedFormat' => $feed['format'],
                'newsArchives' => $feed['archives'],
                'maxFeedItems' => $feed['maxItems'],
                'imgSize' => $feed['imgSize'],
            ]);

            $this->connection->delete('tl_news_feed', ['id' => $feed['id']]);
        }

        return $this->createResult(true);
    }

    private function findMatchingRootPage(array $feed): ?int
    {
        $feedBase = preg_replace('/^https?:\/\//', '', $feed['feedBase']);

        $page = $this->connection->fetchOne(
            "SELECT id FROM tl_page WHERE type = 'root' AND dns = :dns AND language = :language LIMIT 1",
            ['dns' => $feedBase, 'language' => $feed['language']]
        );

        // Find first root page, if none matches by dns and language
        if (!$page) {
            $page = $this->connection->fetchOne("SELECT id FROM tl_page WHERE type = 'root' AND fallback = '1' ORDER BY sorting ASC LIMIT 1");
        }

        return $page;
    }
}
