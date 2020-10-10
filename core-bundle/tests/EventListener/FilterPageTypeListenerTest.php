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
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
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
            ->method('fetchColumn')
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
            ->method('fetchColumn')
            ->with('SELECT type FROM tl_page WHERE id=?', [17])
            ->willReturn('foo')
        ;

        $event = new FilterPageTypeEvent(
            ['foo', 'root', 'error_401', 'error_403', 'error_404'],
            $this->mockDataContainer(17)
        );

        $listener = new FilterPageTypeListener($connection);
        $listener($event);

        $this->assertSame(['foo'], $event->getOptions());
    }

    public function testRemovesErrorTypesAlreadyPresentInTheRootPage(): void
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(FetchMode::COLUMN)
            ->willReturn(['foo', 'error_401', 'error_403'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchColumn')
            ->with('SELECT type FROM tl_page WHERE id=?', [1])
            ->willReturn('root')
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT DISTINCT(type) FROM tl_page WHERE pid=? AND id!=?', [1, 2])
            ->willReturn($statement)
        ;

        $event = new FilterPageTypeEvent(
            ['foo', 'root', 'error_401', 'error_403', 'error_404'],
            $this->mockDataContainer(1, 2)
        );

        $listener = new FilterPageTypeListener($connection);
        $listener($event);

        $this->assertSame(['foo', 'error_404'], $event->getOptions());
    }

    /**
     * @return DataContainer&MockObject
     */
    private function mockDataContainer(?int $pid, int $id = null): DataContainer
    {
        $activeRecord = array_filter(
            [
                'id' => $id,
                'pid' => $pid,
            ],
            static function ($v) {
                return null !== $v;
            }
        );
        dump($activeRecord);
        /** @var DataContainer&MockObject */
        return $this->mockClassWithProperties(
            DataContainer::class,
            ['activeRecord' => empty($activeRecord) ? null : (object) $activeRecord]
        );
    }
}
