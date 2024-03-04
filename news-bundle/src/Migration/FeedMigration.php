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
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class FeedMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection) {
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
            'feedDescription' => 'text NULL',
            'imgSize' => "varchar(255) NOT NULL default ''",
        ];

        foreach ($newFields as $field => $definition) {
            if (\in_array(strtolower($field), $columns, true)) {
                continue;
            }

            $this->connection->executeStatement("ALTER TABLE tl_page ADD $field $definition");
        }

        // Migrate data from `tl_news_feeds` to `tl_page` and update `tl_layout`
        $feeds = $this->connection->fetchAllAssociative('SELECT * FROM tl_news_feed');
        $layouts = $this->connection->fetchAllKeyValue('SELECT id, newsfeeds FROM tl_layout WHERE newsfeeds IS NOT NULL');
        $mapping = [];

        foreach ($feeds as $feed) {
            $rootPage = $this->findMatchingRootPage($feed);

            if (!$rootPage) {
                return $this->createResult(false, 'Could not migrate feed "'.$feed['title'].'" because there is no root page');
            }

            $this->connection->insert('tl_page', [
                'type' => 'news_feed',
                'pid' => $rootPage,
                'tstamp' => $feed['tstamp'],
                'title' => $feed['title'],
                'alias' => 'share/'.$feed['alias'],
                'feedSource' => $feed['source'],
                'feedFormat' => $feed['format'],
                'newsArchives' => $feed['archives'],
                'maxFeedItems' => $feed['maxItems'],
                'feedDescription' => $feed['description'],
                'imgSize' => $feed['imgSize'],
                'published' => 1
            ]);

            $mapping[$feed['id']] = $this->connection->lastInsertId();

            $this->connection->delete('tl_news_feed', ['id' => $feed['id']]);
        }

        foreach ($layouts as $layoutId => $newsfeeds) {
            $newsfeeds = StringUtil::deserialize($newsfeeds);

            if (!\is_array($newsfeeds)) {
                continue;
            }

            foreach ($newsfeeds as $k => $v) {
                $newsfeeds[$k] = $mapping[$v] ?? $v;
            }

            $this->connection->update('tl_layout', ['newsfeeds' => serialize($newsfeeds)], ['id' => $layoutId]);
        }

        return $this->createResult(true);
    }

    private function findMatchingRootPage(array $feed): int|null
    {
        $feedBase = preg_replace('/^https?:\/\//', '', $feed['feedBase']);

        $page = $this->connection->fetchOne(
            "SELECT id FROM tl_page WHERE type = 'root' AND dns = :dns AND language = :language LIMIT 1",
            ['dns' => $feedBase, 'language' => $feed['language']],
        );

        // Find first root page, if none matches by dns and language
        if (!$page) {
            $page = $this->connection->fetchOne("SELECT id FROM tl_page WHERE type = 'root' AND fallback = 1 ORDER BY sorting ASC LIMIT 1");
        }

        return $page ?: null;
    }
}
