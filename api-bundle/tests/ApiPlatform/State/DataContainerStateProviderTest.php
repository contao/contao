<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Provider\ReadProvider;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProvider;
use Contao\ApiBundle\DataContainer\DataContainerPage;
use Contao\ApiBundle\DataContainer\DataContainerRecords;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DataContainerStateProviderTest extends TestCase
{
    public function testReadsExistingRecordsForItemOperations(): void
    {
        $record = new DataContainerRecord('tl_content', ['headline' => 'Existing'], 17);

        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->exactly(3))
            ->method('find')
            ->with('tl_content', 17)
            ->willReturn($record)
        ;

        $provider = new ReadProvider(new DataContainerStateProvider($records, new Pagination()));

        foreach ([new Get(), new Patch(), new Delete()] as $operation) {
            $operation = $operation->withRead(true)->withExtraProperties(['contao' => ['table' => 'tl_content']]);
            $this->assertSame($record, $provider->provide($operation, ['id' => 17]));
        }
    }

    public function testReturnsNotFoundForMissingRecords(): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->once())
            ->method('find')
            ->with('tl_content', 17)
            ->willReturn(null)
        ;

        $provider = new ReadProvider(new DataContainerStateProvider($records, new Pagination()));
        $operation = new Get(read: true, extraProperties: ['contao' => ['table' => 'tl_content']]);

        $this->expectException(NotFoundHttpException::class);
        $provider->provide($operation, ['id' => 17]);
    }

    public function testReturnsTheRequestedCollectionPage(): void
    {
        $page = new DataContainerPage([new DataContainerRecord('tl_content', [], 17)], 2);

        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->once())
            ->method('list')
            ->with('tl_content', 2, [], 30)
            ->willReturn($page)
        ;

        $operation = new GetCollection(extraProperties: ['contao' => ['table' => 'tl_content']]);

        $this->assertSame($page, new DataContainerStateProvider($records, new Pagination())->provide($operation, context: ['filters' => ['page' => 2]]));
    }

    #[DataProvider('providePageSizes')]
    public function testUsesTheRequestedPageSizeWithinTheMaximum(array $filters, int $limit, bool $request): void
    {
        $page = new DataContainerPage([], 2, $limit);

        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->once())
            ->method('list')
            ->with('tl_content', 2, ['id' => '7', 'table' => 'tl_page'], $limit)
            ->willReturn($page)
        ;

        $operation = new GetCollection(
            paginationClientItemsPerPage: true,
            paginationItemsPerPage: 30,
            paginationMaximumItemsPerPage: 100,
            extraProperties: ['contao' => ['table' => 'tl_content']],
        );

        $filters += ['page' => '2', 'parent' => '7', 'ptable' => 'tl_page'];
        $context = $request ? ['request' => new Request($filters)] : ['filters' => $filters];

        $this->assertSame($page, new DataContainerStateProvider($records, new Pagination())->provide($operation, context: $context));
        $this->assertSame((float) $limit, $page->getItemsPerPage());
    }

    public static function providePageSizes(): iterable
    {
        yield 'default' => [[], 30, false];
        yield 'smaller' => [['itemsPerPage' => '10'], 10, false];
        yield 'larger' => [['itemsPerPage' => '60'], 60, true];
        yield 'maximum' => [['itemsPerPage' => '100'], 100, false];
        yield 'capped' => [['itemsPerPage' => '999'], 100, true];
    }

    #[DataProvider('provideInvalidPagination')]
    public function testRejectsInvalidPaginationBeforeListing(array $filters): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->never())
            ->method('list')
        ;

        $operation = new GetCollection(paginationClientItemsPerPage: true, paginationMaximumItemsPerPage: 100, extraProperties: ['contao' => ['table' => 'tl_content']]);
        $this->expectException(InvalidArgumentException::class);

        new DataContainerStateProvider($records, new Pagination())->provide($operation, context: ['filters' => $filters]);
    }

    public static function provideInvalidPagination(): iterable
    {
        yield [['page' => 0]];
        yield [['page' => -1]];
        yield [['itemsPerPage' => 0]];
        yield [['itemsPerPage' => -1]];
        yield [['itemsPerPage' => 'invalid']];
        yield [['page' => PHP_INT_MAX, 'itemsPerPage' => 100]];
    }

    public function testPassesTheSortingChoiceToTheDataContainer(): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->once())
            ->method('list')
            ->with('tl_content', 1, [], 30, ['title DESC'])
            ->willReturn(new DataContainerPage([], 1))
        ;
        $operation = new GetCollection(extraProperties: ['contao' => ['table' => 'tl_content']]);

        new DataContainerStateProvider($records, new Pagination())->provide($operation, context: ['request' => new Request(['sort' => 'title DESC'])]);
    }

    public function testRejectsMultipleSortingChoicesUntilSupported(): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->never())
            ->method('list')
        ;
        $operation = new GetCollection(extraProperties: ['contao' => ['table' => 'tl_content']]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one sorting choice is currently supported.');

        new DataContainerStateProvider($records, new Pagination())->provide($operation, context: ['request' => new Request(['sort' => 'title DESC,alias ASC'])]);
    }

    public function testRejectsAnArraySortingChoice(): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->never())
            ->method('list')
        ;
        $operation = new GetCollection(extraProperties: ['contao' => ['table' => 'tl_content']]);
        $this->expectException(InvalidArgumentException::class);

        new DataContainerStateProvider($records, new Pagination())->provide($operation, context: ['filters' => ['sort' => ['title' => 'DESC']]]);
    }

    public function testReturnsNullWhenNoContaoTableIsConfigured(): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->never())
            ->method('find')
        ;

        $this->assertNull(new DataContainerStateProvider($records, new Pagination())->provide(new Get()));
    }
}
