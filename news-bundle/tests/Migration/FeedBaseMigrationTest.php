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

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsBundle\Migration\FeedBaseMigration;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedBaseMigrationTest extends ContaoTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement("CREATE TABLE tl_page (id INTEGER PRIMARY KEY, type VARCHAR(32), feedBase VARCHAR(255) NOT NULL DEFAULT '', newsArchives BLOB)");
        $this->connection->executeStatement('CREATE TABLE tl_news_archive (id INTEGER PRIMARY KEY, jumpTo INTEGER)');
    }

    protected function tearDown(): void
    {
        $this->connection->close();

        parent::tearDown();
    }

    #[DataProvider('getMissingSchema')]
    public function testDoesNotRunBeforeSchemaExists(string $sql): void
    {
        $this->connection->executeStatement($sql);

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $migration = new FeedBaseMigration($this->connection, $framework, $this->createStub(ContentUrlGenerator::class));

        $this->assertFalse($migration->shouldRun());
    }

    public static function getMissingSchema(): iterable
    {
        yield 'missing page table' => ['DROP TABLE tl_page'];
        yield 'missing archive table' => ['DROP TABLE tl_news_archive'];
        yield 'missing feedBase column' => ['ALTER TABLE tl_page DROP COLUMN feedBase'];
        yield 'missing newsArchives column' => ['ALTER TABLE tl_page DROP COLUMN newsArchives'];
    }

    public function testDoesNotRunWithoutEmptyNewsFeeds(): void
    {
        $this->connection->insert('tl_page', ['id' => 1, 'type' => 'regular']);
        $this->connection->insert('tl_page', ['id' => 2, 'type' => 'news_feed', 'feedBase' => 'https://configured.example']);

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $migration = new FeedBaseMigration($this->connection, $framework, $this->createStub(ContentUrlGenerator::class));

        $this->assertFalse($migration->shouldRun());
    }

    #[DataProvider('getTargetUrls')]
    public function testMigratesEmptyFeedBases(string $url, string $expected): void
    {
        $this->connection->insert('tl_news_archive', ['id' => 10, 'jumpTo' => 100]);
        $this->connection->insert('tl_news_archive', ['id' => 20, 'jumpTo' => 200]);
        $this->connection->insert('tl_page', ['id' => 1, 'type' => 'news_feed', 'newsArchives' => serialize([20, 10])]);
        $this->connection->insert('tl_page', ['id' => 2, 'type' => 'regular', 'newsArchives' => serialize([20])]);
        $this->connection->insert('tl_page', ['id' => 3, 'type' => 'news_feed', 'newsArchives' => serialize([20]), 'feedBase' => 'https://configured.example']);
        $this->connection->insert('tl_page', ['id' => 4, 'type' => 'news_feed', 'newsArchives' => serialize([10])]);

        $firstTarget = $this->createStub(PageModel::class);
        $secondTarget = $this->createStub(PageModel::class);

        $pageAdapter = $this->createAdapterStub(['findById']);
        $pageAdapter
            ->method('findById')
            ->willReturnMap([
                [200, $firstTarget],
                [100, $secondTarget],
            ])
        ;

        $framework = $this->createContaoFrameworkMock([PageModel::class => $pageAdapter]);
        $framework
            ->expects($this->exactly(2))
            ->method('initialize')
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->method('generate')
            ->with($this->isInstanceOf(PageModel::class), ['parameters' => '/foo'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturnMap([
                [$firstTarget, ['parameters' => '/foo'], UrlGeneratorInterface::ABSOLUTE_URL, $url],
                [$secondTarget, ['parameters' => '/foo'], UrlGeneratorInterface::ABSOLUTE_URL, 'http://other.example/news.html'],
            ])
        ;

        $migration = new FeedBaseMigration($this->connection, $framework, $urlGenerator);

        $this->assertTrue($migration->shouldRun());
        $this->assertSame('', $this->connection->fetchOne('SELECT feedBase FROM tl_page WHERE id = 1'));
        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame(
            [
                1 => $expected,
                2 => '',
                3 => 'https://configured.example',
                4 => 'http://other.example/',
            ],
            $this->connection->fetchAllKeyValue('SELECT id, feedBase FROM tl_page ORDER BY id'),
        );

        $this->assertFalse($migration->shouldRun());

        // Feeds added by another migration must still be processed on the next pass.
        $this->connection->insert('tl_page', ['id' => 5, 'type' => 'news_feed', 'newsArchives' => serialize([20])]);

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());
        $this->assertSame($expected, $this->connection->fetchOne('SELECT feedBase FROM tl_page WHERE id = 5'));
        $this->assertFalse($migration->shouldRun());
    }

    public static function getTargetUrls(): iterable
    {
        yield 'https' => ['https://example.org/news.html?foo=bar#section', 'https://example.org/'];
        yield 'http' => ['http://example.org/subdirectory/news.html', 'http://example.org/'];
        yield 'scheme and host only' => ['https://user:password@example.org:8443/news.html', 'https://example.org/'];
    }

    public function testTriesArchivesInOrderAndStopsAfterMigratingEachFeed(): void
    {
        $this->connection->insert('tl_news_archive', ['id' => 10, 'jumpTo' => 100]);
        $this->connection->insert('tl_news_archive', ['id' => 20, 'jumpTo' => 200]);
        $this->connection->insert('tl_news_archive', ['id' => 30, 'jumpTo' => 300]);
        $this->connection->insert('tl_page', ['id' => 1, 'type' => 'news_feed', 'newsArchives' => serialize([99, 10, 20, 30])]);
        $this->connection->insert('tl_page', ['id' => 2, 'type' => 'news_feed', 'newsArchives' => serialize([30, 20])]);

        $unroutableTarget = $this->createStub(PageModel::class);
        $firstTarget = $this->createStub(PageModel::class);
        $secondTarget = $this->createStub(PageModel::class);

        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->exactly(3))
            ->method('findById')
            ->willReturnMap([
                [100, $unroutableTarget],
                [200, $firstTarget],
                [300, $secondTarget],
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->exactly(3))
            ->method('generate')
            ->with($this->isInstanceOf(PageModel::class), ['parameters' => '/foo'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturnCallback(
                static function (PageModel $page) use ($unroutableTarget, $firstTarget): string {
                    if ($page === $unroutableTarget) {
                        throw new RouteNotFoundException();
                    }

                    return $page === $firstTarget ? 'https://first.example/news.html' : 'http://second.example/archive.html';
                },
            )
        ;

        $migration = new FeedBaseMigration($this->connection, $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]), $urlGenerator);

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame(
            [
                1 => 'https://first.example/',
                2 => 'http://second.example/',
            ],
            $this->connection->fetchAllKeyValue('SELECT id, feedBase FROM tl_page ORDER BY id'),
        );

        $this->assertFalse($migration->shouldRun());
    }

    #[DataProvider('getMissingTargets')]
    public function testSkipsFeedsWithoutTargetPages(array|null $archives, int $jumpTo, bool $pageExists): void
    {
        $this->connection->insert('tl_news_archive', ['id' => 42, 'jumpTo' => $jumpTo]);
        $this->connection->insert('tl_page', ['id' => 1, 'type' => 'news_feed', 'newsArchives' => null === $archives ? null : serialize($archives)]);

        $pageAdapter = $this->createAdapterStub(['findById']);
        $pageAdapter
            ->method('findById')
            ->willReturn($pageExists ? $this->createStub(PageModel::class) : null)
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $migration = new FeedBaseMigration($this->connection, $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]), $urlGenerator);

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());
        $this->assertSame('', $this->connection->fetchOne('SELECT feedBase FROM tl_page WHERE id = 1'));
        $this->assertFalse($migration->shouldRun());
    }

    public static function getMissingTargets(): iterable
    {
        yield 'null archives' => [null, 100, true];
        yield 'empty archives' => [[], 100, true];
        yield 'deleted archive' => [[99], 100, true];
        yield 'no jumpTo page' => [[42], 0, true];
        yield 'deleted jumpTo page' => [[42], 100, false];
    }

    #[DataProvider('getInvalidTargetUrls')]
    public function testSkipsFeedsWithoutAbsoluteTargetUrls(\Throwable|string $url): void
    {
        $this->connection->insert('tl_news_archive', ['id' => 42, 'jumpTo' => 100]);
        $this->connection->insert('tl_page', ['id' => 1, 'type' => 'news_feed', 'newsArchives' => serialize([42])]);

        $pageAdapter = $this->createConfiguredAdapterStub(['findById' => $this->createStub(PageModel::class)]);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $invocation = $urlGenerator
            ->expects($this->once())
            ->method('generate')
        ;

        if ($url instanceof \Throwable) {
            $invocation->willThrowException($url);
        } else {
            $invocation->willReturn($url);
        }

        $migration = new FeedBaseMigration($this->connection, $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]), $urlGenerator);

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());
        $this->assertSame('', $this->connection->fetchOne('SELECT feedBase FROM tl_page WHERE id = 1'));
        $this->assertFalse($migration->shouldRun());
    }

    public static function getInvalidTargetUrls(): iterable
    {
        yield 'unroutable page' => [new RouteNotFoundException()];
        yield 'relative URL' => ['/news.html'];
        yield 'URL without scheme' => ['//example.org/news.html'];
    }

    #[DataProvider('getInvalidArguments')]
    public function testDoesNotCatchInvalidArgumentExceptions(\InvalidArgumentException|string $url): void
    {
        $this->connection->insert('tl_news_archive', ['id' => 42, 'jumpTo' => 100]);
        $this->connection->insert('tl_news_archive', ['id' => 43, 'jumpTo' => 200]);
        $this->connection->insert('tl_page', ['id' => 1, 'type' => 'news_feed', 'newsArchives' => serialize([42, 43])]);

        $pageAdapter = $this->createConfiguredAdapterStub(['findById' => $this->createStub(PageModel::class)]);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $invocation = $urlGenerator
            ->expects($this->once())
            ->method('generate')
        ;

        if ($url instanceof \InvalidArgumentException) {
            $invocation->willThrowException($url);
        } else {
            $invocation->willReturn($url);
        }

        $migration = new FeedBaseMigration($this->connection, $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]), $urlGenerator);

        $this->expectException(\InvalidArgumentException::class);

        $migration->run();
    }

    public static function getInvalidArguments(): iterable
    {
        yield 'URL generation exception' => [new \InvalidArgumentException()];
        yield 'malformed URL' => ['https://example.org:99999/news.html'];
    }
}
