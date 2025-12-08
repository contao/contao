<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\PageSearchListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Search;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;

class PageSearchListenerTest extends TestCase
{
    #[DataProvider('purgeSearchIndexProvider')]
    public function testPageChanges(string $field, string $newValue, array $recordData, bool $shouldPurgeSearchIndex): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($shouldPurgeSearchIndex ? $this->once() : $this->never())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid = :pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->createAdapterStub(['removeEntry']);
        if ($shouldPurgeSearchIndex) {
            $search
                ->expects($this->once())
                ->method('removeEntry')
                ->with('uri')
            ;
        } else {
            $search
                ->expects($this->never())
                ->method('removeEntry')
            ;
        }

        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($recordData)
        ;

        $listener = new PageSearchListener(
            $this->createContaoFrameworkStub([Search::class => $search]),
            $connection,
        );

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

    public static function purgeSearchIndexProvider(): iterable
    {
        yield 'Test alias (1) unchanged should not purge the search entry' => [
            'alias', 'foo', ['alias' => 'foo'], false,
        ];

        yield 'Test alias (2) change should purge the search entry' => [
            'alias', 'bar', ['alias' => 'foo'], true,
        ];

        yield 'Test searchIndexer (1) unchanged should not purge the search index.' => [
            'searchIndexer', 'always_index', ['searchIndexer' => 'always_index'], false,
        ];

        yield 'Test searchIndexer (2) change to never_index should purge the search index.' => [
            'searchIndexer', 'never_index', ['searchIndexer' => ''], true,
        ];

        yield 'Test searchIndexer (3) change to always_index should not purge the search index.' => [
            'searchIndexer', 'always_index', ['searchIndexer' => ''], false,
        ];

        yield 'Test searchIndexer (4) change to blank should not purge the search index if robots is set to index.' => [
            'searchIndexer', '', ['searchIndexer' => 'always_index', 'robots' => 'index,follow'], false,
        ];

        yield 'Test searchIndexer (5) change to blank should purge the search index if robots is set to noindex.' => [
            'searchIndexer', '', ['searchIndexer' => 'always_index', 'robots' => 'noindex,follow'], true,
        ];

        yield 'Test robots (1) unchanged should not purge the search entry.' => [
            'robots', 'index,follow', ['robots' => 'index,follow'], false,
        ];

        yield 'Test robots (2) change to index should not purge the search entry if searchIndexer is set to blank.' => [
            'robots', 'index,follow', ['robots' => 'noindex,follow', 'searchIndexer' => ''], false,
        ];

        yield 'Test robots (3) change to index should not purge the search entry if searchIndexer is set to always_index.' => [
            'robots', 'index,follow', ['robots' => 'noindex,follow', 'searchIndexer' => 'always_index'], false,
        ];

        yield 'Test robots (4) change to noindex should purge the search entry if searchIndexer is set to blank.' => [
            'robots', 'noindex,follow', ['robots' => 'index,follow', 'searchIndexer' => ''], true,
        ];

        yield 'Test robots (5) change to noindex should not purge the search entry if searchIndexer is set to always_index.' => [
            'robots', 'noindex,follow', ['robots' => 'index,follow', 'searchIndexer' => 'always_index'], false,
        ];

        yield 'Test robots (6) change to noindex should purge the search entry if searchIndexer is set to never_index.' => [
            'robots', 'noindex,follow', ['robots' => 'index,follow', 'searchIndexer' => 'never_index'], true,
        ];
    }

    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid = :pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->createAdapterStub(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => 17]);

        $listener = new PageSearchListener(
            $this->createContaoFrameworkStub([Search::class => $search]),
            $connection,
        );

        $listener->onDelete($dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->createAdapterStub(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => null]);

        $listener = new PageSearchListener(
            $this->createContaoFrameworkStub([Search::class => $search]),
            $connection,
        );

        $listener->onDelete($dc);
    }
}
