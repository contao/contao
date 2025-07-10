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

use Contao\NewsBundle\EventListener\DataContainer\PageListener;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PageListenerTest extends ContaoTestCase
{
    public function testAdminHasAccessToAllArchives(): void
    {
        $archives = [
            42 => 'The answer to life, the universe and everything',
            84 => 'Example news archive',
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
            ->with('tl_news_archive')
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
                ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE,
                $this->callback(static fn (int $id): bool => \in_array($id, [42, 84], true)),
            )
            ->willReturn(true)
        ;

        $listener = new PageListener($connection, $authorizationChecker);

        $this->assertSame(
            [
                42 => 'The answer to life, the universe and everything',
                84 => 'Example news archive',
            ],
            $listener->getAllowedArchives(),
        );
    }

    public function testEditorHasAccessToAllowedArchives(): void
    {
        $archives = [
            42 => 'The answer to life, the universe and everything',
            84 => 'Example news archive',
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
            ->with('tl_news_archive')
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
                ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE,
                $this->callback(static fn (int $id): bool => \in_array($id, [42, 84], true)),
            )
            ->willReturnCallback(static fn (string $attribute, int $id): bool => 42 === $id)
        ;

        $listener = new PageListener($connection, $authorizationChecker);

        $this->assertSame([42 => 'The answer to life, the universe and everything'], $listener->getAllowedArchives());
    }
}
