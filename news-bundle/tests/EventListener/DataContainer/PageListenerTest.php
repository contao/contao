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

use Contao\BackendUser;
use Contao\NewsBundle\EventListener\DataContainer\PageListener;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Symfony\Component\Security\Core\Security;

class PageListenerTest extends ContaoTestCase
{
    public function testAdminHasAccessToAllArchives(): void
    {
        $archives = [
            [
                'id' => 42,
                'title' => 'The answer to life, the universe and everything',
            ],
            [
                'id' => 84,
                'title' => 'Example news archive',
            ],
        ];

        $user = $this->mockClassWithProperties(BackendUser::class, [
            'id' => 1,
        ]);

        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($archives)
        ;

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
            ->expects($this->never())
            ->method('where')
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true)
        ;

        $listener = new PageListener($connection, $security);
        $this->assertSame([
            42 => 'The answer to life, the universe and everything',
            84 => 'Example news archive',
        ], $listener->getAllowedArchives());
    }

    public function testEditorHasAccessToAllowedArchives(): void
    {
        $archives = [
            [
                'id' => 42,
                'title' => 'The answer to life, the universe and everything',
            ],
        ];

        $user = $this->mockClassWithProperties(BackendUser::class, [
            'id' => 1,
        ]);

        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($archives)
        ;

        $expr = $this->createMock(ExpressionBuilder::class);
        $expr
            ->expects($this->once())
            ->method('in')
            ->with('id', $user->news)
        ;

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
            ->method('expr')
            ->willReturn($expr)
        ;
        $queryBuilder
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false)
        ;

        $listener = new PageListener($connection, $security);
        $this->assertSame([
            42 => 'The answer to life, the universe and everything',
        ], $listener->getAllowedArchives());
    }
}
