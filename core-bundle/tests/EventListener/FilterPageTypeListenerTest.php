<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Event\FilterPageTypeEvent;
use Contao\CoreBundle\EventListener\FilterPageTypeListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;

class FilterPageTypeListenerTest extends TestCase
{
    public function testDoesNothingWithoutActiveRecord(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new FilterPageTypeEvent(['foo', 'bar'], $this->mockDataContainer(null));

        $listener = new FilterPageTypeListener($connection);
        $listener($event);

        $this->assertSame(['foo', 'bar'], $event->getOptions());
    }

    public function testOnlyAllowsRootTypeWithoutPid(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new FilterPageTypeEvent(['foo', 'bar', 'root'], $this->mockDataContainer(0));

        $listener = new FilterPageTypeListener($connection);
        $listener($event);

        $this->assertSame(['root'], $event->getOptions());
    }

    public function testRemovesRootTypeIfHasParentPage(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn('foo')
        ;

        $event = new FilterPageTypeEvent(['foo', 'root'], $this->mockDataContainer(17));

        $listener = new FilterPageTypeListener($connection);
        $listener($event);

        $this->assertSame(['foo'], $event->getOptions());
    }

    public function testRemovesErrorTypesIfParentIsNotRoot(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT type FROM tl_page WHERE id=?', [17])
            ->willReturn('foo')
        ;

        $event = new FilterPageTypeEvent(
            ['foo', 'root', 'error_401', 'error_403', 'error_404', 'error_503'],
            $this->mockDataContainer(17),
        );

        $listener = new FilterPageTypeListener($connection);
        $listener($event);

        $this->assertSame(['foo'], $event->getOptions());
    }

    public function testRemovesErrorTypesAlreadyPresentInTheRootPage(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT type FROM tl_page WHERE id=?', [1])
            ->willReturn('root')
        ;

        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT DISTINCT(type) FROM tl_page WHERE pid=? AND id!=?', [1, 2])
            ->willReturn(['foo', 'error_401', 'error_403'])
        ;

        $event = new FilterPageTypeEvent(
            ['foo', 'root', 'error_401', 'error_403', 'error_404'],
            $this->mockDataContainer(1, 2),
        );

        $listener = new FilterPageTypeListener($connection);
        $listener($event);

        $this->assertSame(['foo', 'error_404'], $event->getOptions());
    }

    /**
     * @return DataContainer&MockObject
     */
    private function mockDataContainer(int|null $pid, int|null $id = null): DataContainer
    {
        $currentRecord = array_filter(['id' => $id, 'pid' => $pid], static fn ($v): bool => null !== $v);

        $mock = $this->createMock(DataContainer::class);
        $mock
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn($currentRecord ?: null)
        ;

        return $mock;
    }
}
