<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\NewsBundle\EventListener\DataContainer\NewsSearchListener;
use Contao\NewsModel;
use Contao\Search;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsSearchListenerTest extends TestCase
{
    #[DataProvider('purgeSearchEntryProvider')]
    public function testNewsChanges(string $field, string $newValue, array $recordData, array|null $readerPageSettings, bool $shouldRemoveSearchEntry): void
    {
        $newsModel = $this->createStub(NewsModel::class);

        $search = $this->createAdapterMock(['removeEntry']);
        $search
            ->expects($shouldRemoveSearchEntry ? $this->once() : $this->never())
            ->method('removeEntry')
            ->with('uri')
        ;

        $framework = $this->createContaoFrameworkStub([
            NewsModel::class => $this->createConfiguredAdapterStub(['findById' => $newsModel]),
            Search::class => $search,
        ]);

        $connection = $this->createMock(Connection::class);

        if (null !== $readerPageSettings) {
            $connection
                ->expects($this->once())
                ->method('fetchAssociative')
                ->willReturn($readerPageSettings)
            ;
        } else {
            $connection
                ->expects($this->never())
                ->method($this->anything())
            ;
        }

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($shouldRemoveSearchEntry ? $this->once() : $this->never())
            ->method('generate')
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($recordData)
        ;

        $listener = new NewsSearchListener($framework, $connection, $urlGenerator);

        switch ($field) {
            case 'alias':
                $listener->onSaveAlias($newValue, $dc);
                break;
            case 'searchIndexer':
                $listener->onSaveSearchIndexer($newValue, $dc);
                break;
            case 'robots':
                $listener->onSaveRobots($newValue, $dc);
                break;
        }
    }

    public static function purgeSearchEntryProvider(): iterable
    {
        yield 'Test alias (1) unchanged should not purge the search entry' => [
            'alias', 'foo', ['alias' => 'foo'], null, false,
        ];

        yield 'Test alias (2) change should purge the search entry' => [
            'alias', 'bar', ['alias' => 'foo'], null, true,
        ];

        yield 'Test searchIndexer (1) unchanged should not purge the search entry.' => [
            'searchIndexer', 'always_index', ['searchIndexer' => 'always_index'], null, false,
        ];

        yield 'Test searchIndexer (2) change to always_index should not purge the search entry.' => [
            'searchIndexer', 'always_index', ['searchIndexer' => ''], null, false,
        ];

        yield 'Test searchIndexer (3) change to never_index should purge the search entry.' => [
            'searchIndexer', 'never_index', ['searchIndexer' => ''], null, true,
        ];

        yield 'Test searchIndexer (4) change to blank should not purge the search entry if the reader page has searchIndexer:always_index.' => [
            'searchIndexer', '', ['searchIndexer' => 'always_index', 'pid' => 5], ['searchIndexer' => 'always_index'], false,
        ];

        yield 'Test searchIndexer (5) change to blank should purge the search entry if the reader page has searchIndexer:never_index.' => [
            'searchIndexer', '', ['searchIndexer' => 'always_index', 'pid' => 5], ['searchIndexer' => 'never_index'], true,
        ];

        yield 'Test searchIndexer (6) change to blank should not purge the search entry if robots is set to index the reader page has searchIndexer:blank.' => [
            'searchIndexer', '', ['robots' => 'index,follow', 'searchIndexer' => 'always_index', 'pid' => 5], ['searchIndexer' => ''], false,
        ];

        yield 'Test searchIndexer (7) change to blank should purge the search entry if robots is set to noindex and the reader page has searchIndexer:blank.' => [
            'searchIndexer', '', ['robots' => 'noindex,follow', 'searchIndexer' => 'always_index', 'pid' => 5], ['searchIndexer' => ''], true,
        ];

        yield 'Test searchIndexer (8) change to blank should not purge the search entry if robots is not set and the reader page has searchIndexer:blank and robots:index.' => [
            'searchIndexer', '', ['searchIndexer' => 'always_index', 'pid' => 5], ['searchIndexer' => '', 'robots' => 'index,follow'], false,
        ];

        yield 'Test searchIndexer (9) change to blank should purge the search entry if robots is not set and the reader page has searchIndexer:blank and robots:noindex.' => [
            'searchIndexer', '', ['searchIndexer' => 'always_index', 'pid' => 5], ['searchIndexer' => '', 'robots' => 'noindex,follow'], true,
        ];

        yield 'Test robots (1) unchanged should not purge the search entry.' => [
            'robots', 'index,follow', ['robots' => 'index,follow'], null, false,
        ];

        yield 'Test robots (2) change to index should not purge the search entry if searchIndexer is set to always_index.' => [
            'robots', 'index,follow', ['robots' => '', 'searchIndexer' => 'always_index'], null, false,
        ];

        yield 'Test robots (3) change to index should purge the search entry if searchIndexer is set to never_index' => [
            'robots', 'index,follow', ['robots' => '', 'searchIndexer' => 'never_index'], null, true,
        ];

        yield 'Test robots (4) change to blank should not purge the search entry if searchIndexer is set to always_index' => [
            'robots', '', ['robots' => 'index,follow', 'searchIndexer' => 'always_index'], null, false,
        ];

        yield 'Test robots (5) change to blank should purge the search entry if searchIndexer is set to never_index' => [
            'robots', '', ['robots' => 'index,follow', 'searchIndexer' => 'never_index'], null, true,
        ];

        yield 'Test robots (6) change to noindex should not purge the search entry if searchIndexer is set to always_index' => [
            'robots', 'noindex,follow', ['robots' => 'index,follow', 'searchIndexer' => 'always_index'], null, false,
        ];

        yield 'Test robots (7) change to noindex should purge the search entry if searchIndexer is set to never_index' => [
            'robots', 'noindex,follow', ['robots' => 'index,follow', 'searchIndexer' => 'never_index'], null, true,
        ];

        yield 'Test robots (8) change to index should not purge the search entry if searchIndexer is blank and the reader page has searchIndexer:blank' => [
            'robots', 'index,follow', ['robots' => '', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => ''], false,
        ];

        yield 'Test robots (9) change to index should not purge the search entry if searchIndexer is blank and the reader page has searchIndexer:always_index' => [
            'robots', 'index,follow', ['robots' => '', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => 'always_index'], false,
        ];

        yield 'Test robots (10) change to index should purge the search entry if searchIndexer is blank and the reader page has searchIndexer:never_index' => [
            'robots', 'index,follow', ['robots' => '', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => 'never_index'], true,
        ];

        yield 'Test robots (11) change to noindex should purge the search entry if searchIndexer is blank and the reader page has searchIndexer:blank' => [
            'robots', 'noindex,follow', ['robots' => '', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => ''], true,
        ];

        yield 'Test robots (12) change to noindex should not purge the search entry if searchIndexer is blank and the reader page has searchIndexer:always_index' => [
            'robots', 'noindex,follow', ['robots' => '', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => 'always_index'], false,
        ];

        yield 'Test robots (13) change to noindex should purge the search entry if searchIndexer is blank and the reader page has searchIndexer:never_index' => [
            'robots', 'noindex,follow', ['robots' => '', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => 'never_index'], true,
        ];

        yield 'Test robots (14) change to blank should not purge the search entry if searchIndexer is blank and the reader page has searchIndexer:blank and robots:index' => [
            'robots', '', ['robots' => 'index,follow', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => '', 'robots' => 'index,follow'], false,
        ];

        yield 'Test robots (15) change to blank should not purge the search entry if searchIndexer is blank and the reader page has searchIndexer:always_index' => [
            'robots', '', ['robots' => 'index,follow', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => 'always_index'], false,
        ];

        yield 'Test robots (16) change to blank should purge the search entry if searchIndexer is blank and the reader page has searchIndexer:never_index' => [
            'robots', '', ['robots' => 'index,follow', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => 'never_index'], true,
        ];

        yield 'Test robots (17) change to blank should purge the search entry if searchIndexer is blank and the reader page has searchIndexer:blank and robots:noindex' => [
            'robots', '', ['robots' => 'index,follow', 'searchIndexer' => '', 'pid' => 5], ['searchIndexer' => '', 'robots' => 'noindex,follow'], true,
        ];
    }

    #[DataProvider('deleteProvider')]
    public function testOnDelete(array $recordData, bool $shouldRemoveSearchEntry): void
    {
        $newsModel = $this->createStub(NewsModel::class);

        $search = $this->createAdapterMock(['removeEntry']);
        $search
            ->expects($shouldRemoveSearchEntry ? $this->once() : $this->never())
            ->method('removeEntry')
            ->with('uri')
        ;

        $framework = $this->createContaoFrameworkStub([
            NewsModel::class => $this->createConfiguredAdapterStub(['findById' => $newsModel]),
            Search::class => $search,
        ]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($shouldRemoveSearchEntry ? $this->once() : $this->never())
            ->method('generate')
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => $recordData['id']]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($recordData)
        ;

        $listener = new NewsSearchListener($framework, $connection, $urlGenerator);
        $listener->onDelete($dc);
    }

    public static function deleteProvider(): iterable
    {
        yield 'Test purges the search entry on delete when id is present' => [
            ['id' => 17], true,
        ];

        yield 'Test does not purge the search entry without id' => [
            ['id' => null], false,
        ];
    }
}
