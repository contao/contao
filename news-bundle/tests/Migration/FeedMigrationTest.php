<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Migration;

use Contao\CoreBundle\Migration\MigrationResult;
use Contao\NewsBundle\Migration\FeedMigration;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Psr\Log\NullLogger;

class FeedMigrationTest extends ContaoTestCase
{
    public function testDoesNotRunIfFeedTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_news_feed'])
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $migration = new FeedMigration($connection, new NullLogger());

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfNoLegacyFeedsExist(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_news_feed'])
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(0)
        ;

        $migration = new FeedMigration($connection, new NullLogger());

        $this->assertFalse($migration->shouldRun());
    }

    public function testMigratesLegacyFeedsToPages(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_news_feed'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->with('tl_page')
            ->willReturn(['newsArchives', 'feedFormat', 'feedSource', 'maxFeedItems', 'feedFeatured', 'imgSize'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->with('SELECT * FROM tl_news_feed')
            ->willReturn([[
                'id' => 1,
                'tstamp' => 16000000,
                'title' => 'Latest news',
                'alias' => 'latest-news',
                'description' => 'This is an example newsfeed',
                'archives' => serialize([42]),
                'maxItems' => 0,
                'format' => 'rss',
                'source' => 'source_teaser',
                'imgSize' => null,
                'language' => 'en',
                'feedBase' => 'https://example.org',
            ]])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnMap([
                ['SELECT COUNT(*) FROM tl_news_feed', [], [], 1],
                ["SELECT id FROM tl_page WHERE type = 'root' AND dns = :dns AND language = :language LIMIT 1", ['dns' => 'example.org', 'language' => 'en'], [], 1],
            ])
        ;

        $connection
            ->expects($this->once())
            ->method('insert')
            ->with(
                'tl_page',
                [
                    'pid' => 1,
                    'type' => 'news_feed',
                    'title' => 'Latest news',
                    'alias' => 'share/latest-news',
                    'feedSource' => 'source_teaser',
                    'feedFormat' => 'rss',
                    'newsArchives' => serialize([42]),
                    'maxFeedItems' => 0,
                    'feedDescription' => 'This is an example newsfeed',
                    'imgSize' => null,
                    'tstamp' => 16000000,
                ],
            )
        ;

        $connection
            ->expects($this->once())
            ->method('delete')
            ->with('tl_news_feed', ['id' => 1])
        ;

        $migration = new FeedMigration($connection, new NullLogger());

        $this->assertTrue($migration->shouldRun());
        $this->assertEquals(new MigrationResult(true, 'Contao\NewsBundle\Migration\FeedMigration executed successfully'), $migration->run());
    }

    public function testMigratesLegacyFeedToFirstRootPage(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_news_feed'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->with('tl_page')
            ->willReturn(['newsArchives', 'feedFormat', 'feedSource', 'maxFeedItems', 'feedFeatured', 'imgSize'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->with('SELECT * FROM tl_news_feed')
            ->willReturn([[
                'id' => 1,
                'tstamp' => 16000000,
                'title' => 'Latest news',
                'alias' => 'latest-news',
                'description' => 'This is an example newsfeed',
                'archives' => serialize([42]),
                'maxItems' => 0,
                'format' => 'rss',
                'source' => 'source_teaser',
                'imgSize' => null,
                'language' => 'en',
                'feedBase' => 'https://example.org',
            ]])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnMap([
                ['SELECT COUNT(*) FROM tl_news_feed', [], [], 1],
                ["SELECT id FROM tl_page WHERE type = 'root' AND dns = :dns AND language = :language LIMIT 1", ['dns' => 'example.org', 'language' => 'en'], [], []],
                ["SELECT id FROM tl_page WHERE type = 'root' AND fallback = '1' ORDER BY sorting ASC LIMIT 1", [], [], 2],
            ])
        ;

        $connection
            ->expects($this->once())
            ->method('insert')
            ->with(
                'tl_page',
                [
                    'pid' => 2,
                    'type' => 'news_feed',
                    'title' => 'Latest news',
                    'alias' => 'share/latest-news',
                    'feedSource' => 'source_teaser',
                    'feedFormat' => 'rss',
                    'newsArchives' => serialize([42]),
                    'maxFeedItems' => 0,
                    'feedDescription' => 'This is an example newsfeed',
                    'imgSize' => null,
                    'tstamp' => 16000000,
                ],
            )
        ;

        $connection
            ->expects($this->once())
            ->method('delete')
            ->with(
                'tl_news_feed',
                [
                    'id' => 1,
                ],
            )
        ;

        $migration = new FeedMigration($connection, new NullLogger());

        $this->assertTrue($migration->shouldRun());
        $this->assertEquals(new MigrationResult(true, 'Contao\NewsBundle\Migration\FeedMigration executed successfully'), $migration->run());
    }
}
