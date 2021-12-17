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
use PHPUnit\Framework\MockObject\MockObject;

class PageSearchListenerTest extends TestCase
{
    public function testPurgesTheSearchIndexOnAliasChange(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 17,
                'activeRecord' => (object) [
                    'alias' => 'foo',
                ],
            ]
        );

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->purgeSearchIndexOnAliasChange('bar', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedAlias(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 17,
                'activeRecord' => (object) [
                    'alias' => 'foo',
                ],
            ]
        );

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->purgeSearchIndexOnAliasChange('foo', $dc);
    }

    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 17,
            ]
        );

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->purgeSearchIndexOnDelete($dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => null,
            ]
        );

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->purgeSearchIndexOnDelete($dc);
    }
}
