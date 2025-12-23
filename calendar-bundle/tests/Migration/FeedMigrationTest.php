<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Migration;

use Contao\CalendarBundle\Migration\FeedMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySQLSchemaManager;

class FeedMigrationTest extends ContaoTestCase
{
    public function testDoesNotRunIfFeedTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_calendar_feed'])
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $migration = new FeedMigration($connection);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfNoLegacyFeedsExist(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_calendar_feed'])
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

        $migration = new FeedMigration($connection);

        $this->assertFalse($migration->shouldRun());
    }

    public function testMigratesLegacyFeedsToPages(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_calendar_feed'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->with('tl_page')
            ->willReturn(['eventCalendars', 'feedFormat', 'feedSource', 'maxFeedItems', 'feedFeatured', 'imgSize'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->with('SELECT * FROM tl_calendar_feed')
            ->willReturn([[
                'id' => 1,
                'tstamp' => 16000000,
                'title' => 'Some event',
                'alias' => 'some-event',
                'description' => 'This is an example calendar feed',
                'calendars' => serialize([42]),
                'maxItems' => 0,
                'format' => 'rss',
                'source' => 'source_teaser',
                'imgSize' => null,
                'language' => 'en',
                'feedBase' => 'https://example.org',
            ]])
        ;

        $connection
            ->method('fetchAllKeyValue')
            ->with('SELECT id, calendarfeeds FROM tl_layout WHERE calendarfeeds IS NOT NULL')
            ->willReturn([21 => serialize([1])])
        ;

        $connection
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_calendar_feed')
            ->willReturn(1)
        ;

        $connection
            ->method('fetchNumeric')
            ->with("SELECT r.id, MAX(c.sorting) FROM tl_page r LEFT JOIN tl_page c ON c.pid = r.id WHERE r.type = 'root' AND r.dns = :dns AND r.language = :language GROUP BY r.id LIMIT 1", ['dns' => 'example.org', 'language' => 'en'])
            ->willReturn([1, 128])
        ;

        $connection
            ->expects($this->once())
            ->method('insert')
            ->with(
                'tl_page',
                [
                    'pid' => 1,
                    'sorting' => 256,
                    'type' => 'calendar_feed',
                    'title' => 'Some event',
                    'alias' => 'share/some-event',
                    'feedSource' => 'source_teaser',
                    'feedFormat' => 'rss',
                    'eventCalendars' => serialize([42]),
                    'maxFeedItems' => 0,
                    'feedDescription' => 'This is an example calendar feed',
                    'imgSize' => null,
                    'tstamp' => 16000000,
                    'published' => 1,
                    'hide' => 1,
                ],
            )
        ;

        $connection
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(42)
        ;

        $connection
            ->expects($this->once())
            ->method('delete')
            ->with('tl_calendar_feed', ['id' => 1])
        ;

        $connection
            ->expects($this->once())
            ->method('update')
            ->with('tl_layout', ['calendarfeeds' => serialize([42])], ['id' => 21])
        ;

        $migration = new FeedMigration($connection);

        $this->assertTrue($migration->shouldRun());
        $this->assertEquals(new MigrationResult(true, 'Contao\CalendarBundle\Migration\FeedMigration executed successfully'), $migration->run());
    }

    public function testMigratesLegacyFeedToFirstRootPage(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_calendar_feed'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->with('tl_page')
            ->willReturn(['eventCalendars', 'feedFormat', 'feedSource', 'maxFeedItems', 'feedFeatured', 'imgSize'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->with('SELECT * FROM tl_calendar_feed')
            ->willReturn([[
                'id' => 1,
                'tstamp' => 16000000,
                'title' => 'Some event',
                'alias' => 'some-event',
                'description' => 'This is an example calendar feed',
                'calendars' => serialize([42]),
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
            ->with('SELECT COUNT(*) FROM tl_calendar_feed')
            ->willReturn(1)
        ;

        $connection
            ->method('fetchNumeric')
            ->willReturnMap([
                [
                    "SELECT r.id, MAX(c.sorting) FROM tl_page r LEFT JOIN tl_page c ON c.pid = r.id WHERE r.type = 'root' AND r.dns = :dns AND r.language = :language GROUP BY r.id LIMIT 1",
                    ['dns' => 'example.org', 'language' => 'en'],
                    [],
                    [],
                ],
                [
                    "SELECT r.id, MAX(c.sorting) FROM tl_page r LEFT JOIN tl_page c ON c.pid = r.id WHERE r.type = 'root' AND r.fallback = 1 GROUP BY r.id ORDER BY r.sorting ASC LIMIT 1",
                    [],
                    [],
                    [2, 768],
                ],
            ])
        ;

        $connection
            ->expects($this->once())
            ->method('insert')
            ->with(
                'tl_page',
                [
                    'pid' => 2,
                    'sorting' => 896,
                    'type' => 'calendar_feed',
                    'title' => 'Some event',
                    'alias' => 'share/some-event',
                    'feedSource' => 'source_teaser',
                    'feedFormat' => 'rss',
                    'eventCalendars' => serialize([42]),
                    'maxFeedItems' => 0,
                    'feedDescription' => 'This is an example calendar feed',
                    'imgSize' => null,
                    'tstamp' => 16000000,
                    'published' => 1,
                    'hide' => 1,
                ],
            )
        ;

        $connection
            ->expects($this->once())
            ->method('delete')
            ->with(
                'tl_calendar_feed',
                [
                    'id' => 1,
                ],
            )
        ;

        $migration = new FeedMigration($connection);

        $this->assertTrue($migration->shouldRun());
        $this->assertEquals(new MigrationResult(true, 'Contao\CalendarBundle\Migration\FeedMigration executed successfully'), $migration->run());
    }
}
