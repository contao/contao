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
use Contao\ApiBundle\Dto\DataContainerMcpRecord;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\TestCase;

final class DataContainerStateProcessorTest extends TestCase
{
    public function testReturnsTheRecordForCreateAndUpdateOperations(): void
    {
        $processor = new DataContainerStateProcessor();
        $record = new DataContainerRecord('tl_content', ['headline' => 'Example']);
        $operation = new Post()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $this->assertSame($record, $processor->process($record, $operation));

        $operation = new Patch()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $this->assertSame($record, $processor->process($record, $operation, ['id' => 17]));
    }

    public function testConvertsMcpWriteInputToADataContainerRecord(): void
    {
        $processor = new DataContainerStateProcessor();
        $input = new DataContainerMcpRecord(['headline' => 'Example'], 17);
        $operation = new Post()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $record = $processor->process($input, $operation);

        $this->assertInstanceOf(DataContainerRecord::class, $record);
        $this->assertSame('tl_content', $record->table);
        $this->assertSame(['headline' => 'Example'], $record->data);
        $this->assertSame(17, $record->id);
    }

    public function testReturnsNullForDeleteOperations(): void
    {
        $processor = new DataContainerStateProcessor();
        $record = new DataContainerRecord('tl_content', ['headline' => 'Example']);
        $operation = new Delete()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $this->assertNull($processor->process($record, $operation, ['id' => 17]));
    }

    public function testReturnsTheInputWhenItIsNotADataContainerRecord(): void
    {
        $processor = new DataContainerStateProcessor();

        $this->assertSame('foo', $processor->process('foo', new Post()));
    }
}
