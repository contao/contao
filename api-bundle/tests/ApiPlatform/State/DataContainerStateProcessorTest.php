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
