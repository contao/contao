<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\EventListener\DataContainer;

use Contao\CalendarBundle\EventListener\DataContainer\PageListener;
use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PageListenerTest extends ContaoTestCase
{
    public function testAdminHasAccessToAllCalendars(): void
    {
        $archives = [
            42 => 'The answer to life, the universe and everything',
            74913 => 'This is like 3 starships in one',
        ];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('id, title')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with('tl_calendar')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('fetchAllKeyValue')
            ->willReturn($archives)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->with(
                ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR,
                $this->callback(static fn (int $id): bool => \in_array($id, [42, 74913], true)),
            )
            ->willReturn(true)
        ;

        $listener = new PageListener($connection, $authorizationChecker);

        $this->assertSame(
            [
                42 => 'The answer to life, the universe and everything',
                74913 => 'This is like 3 starships in one',
            ],
            $listener->getAllowedCalendars(),
        );
    }

    public function testEditorHasAccessToAllowedCalendars(): void
    {
        $archives = [
            42 => 'The answer to life, the universe and everything',
            74913 => 'This is like 3 starships in one',
        ];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('id, title')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with('tl_calendar')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('fetchAllKeyValue')
            ->willReturn($archives)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->with(
                ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR,
                $this->callback(static fn (int $id): bool => \in_array($id, [42, 74913], true)),
            )
            ->willReturnCallback(
                static fn (string $attribute, int $id): bool => match ($id) {
                    42 => true,
                    74913 => false,
                    default => false,
                },
            )
        ;

        $listener = new PageListener($connection, $authorizationChecker);

        $this->assertSame([42 => 'The answer to life, the universe and everything'], $listener->getAllowedCalendars());
    }
}
