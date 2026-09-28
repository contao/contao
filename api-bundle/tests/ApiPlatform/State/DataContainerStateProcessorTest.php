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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProcessor;
use Contao\ApiBundle\DataContainer\DataContainerRecords;
use Contao\ApiBundle\Dto\DataContainerMove;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\TestCase;

final class DataContainerStateProcessorTest extends TestCase
{
    public function testMovesAnExistingRecordInsteadOfCreatingOne(): void
    {
        $input = new DataContainerMove(42, 'after');
        $persisted = new DataContainerRecord('tl_content', [], 17);

        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->never())
            ->method('create')
        ;

        $records
            ->expects($this->once())
            ->method('move')
            ->with('tl_content', 17, $input)
            ->willReturn($persisted)
        ;

        $operation = new Post(extraProperties: ['contao' => ['table' => 'tl_content', 'action' => 'move']]);

        $this->assertSame($persisted, new DataContainerStateProcessor($records)->process($input, $operation, ['id' => 17]));
    }

    public function testCreatesARecordAndReturnsThePersistedState(): void
    {
        $input = new DataContainerRecord('tl_content', ['headline' => 'Example']);
        $persisted = new DataContainerRecord('tl_content', ['headline' => 'Example', 'alias' => 'example'], 17);

        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->once())
            ->method('create')
            ->with($input)
            ->willReturn($persisted)
        ;

        $operation = new Post(extraProperties: ['contao' => ['table' => 'tl_content']]);

        $this->assertSame($persisted, new DataContainerStateProcessor($records)->process($input, $operation));
    }

    public function testUpdatesARecordAndReturnsThePersistedState(): void
    {
        $input = new DataContainerRecord('tl_content', ['headline' => 'Example'], 17);
        $persisted = new DataContainerRecord('tl_content', ['headline' => 'Saved'], 17);

        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->once())
            ->method('update')
            ->with($input)
            ->willReturn($persisted)
        ;

        $operation = new Patch(extraProperties: ['contao' => ['table' => 'tl_content']]);

        $this->assertSame($persisted, new DataContainerStateProcessor($records)->process($input, $operation, ['id' => 17]));
    }

    public function testDeletesTheRecord(): void
    {
        $record = new DataContainerRecord('tl_content', [], 17);

        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->once())
            ->method('delete')
            ->with($record)
        ;

        $operation = new Delete(extraProperties: ['contao' => ['table' => 'tl_content']]);

        $this->assertNull(new DataContainerStateProcessor($records)->process($record, $operation, ['id' => 17]));
    }

    public function testReturnsUnsupportedInputUnchanged(): void
    {
        $records = $this->createMock(DataContainerRecords::class);
        $records
            ->expects($this->never())
            ->method('create')
        ;

        $this->assertSame('foo', new DataContainerStateProcessor($records)->process('foo', new Post()));
    }
}
