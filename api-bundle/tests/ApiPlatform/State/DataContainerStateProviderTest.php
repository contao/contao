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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\Provider\ReadProvider;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProvider;
use Contao\ApiBundle\DataContainer\DataContainerPage;
use Contao\ApiBundle\DataContainer\DataContainerRecords;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\TestCase;
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
        $provider = new ReadProvider(new DataContainerStateProvider($records));

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
        $provider = new ReadProvider(new DataContainerStateProvider($records));
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
            ->with('tl_content', 2)
            ->willReturn($page)
        ;
        $operation = new GetCollection(extraProperties: ['contao' => ['table' => 'tl_content']]);

        $this->assertSame($page, new DataContainerStateProvider($records)->provide($operation, context: ['filters' => ['page' => 2]]));
    }

    public function testReturnsNullWhenNoContaoTableIsConfigured(): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->never())
            ->method('find')
        ;
        $this->assertNull(new DataContainerStateProvider($records)->provide(new Get()));
    }
}
