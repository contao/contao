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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\McpToolCollection;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProvider;
use Contao\ApiBundle\Dto\DataContainerMcpRecord;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\TestCase;

final class DataContainerStateProviderTest extends TestCase
{
    public function testProvidesARecordForItemOperations(): void
    {
        $provider = new DataContainerStateProvider();
        $operation = new Get()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $record = $provider->provide($operation, ['id' => 17]);

        $this->assertInstanceOf(DataContainerRecord::class, $record);
        $this->assertSame('tl_content', $record->table);
        $this->assertSame(17, $record->id);
        $this->assertSame([], $record->data);
    }

    public function testProvidesARecordForMcpToolItemOperations(): void
    {
        $provider = new DataContainerStateProvider();
        $operation = new McpTool()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $record = $provider->provide($operation, ['id' => 17]);

        $this->assertInstanceOf(DataContainerRecord::class, $record);
        $this->assertSame('tl_content', $record->table);
        $this->assertSame(17, $record->id);
        $this->assertSame([], $record->data);
    }

    public function testProvidesAnEmptyCollectionForCollectionOperations(): void
    {
        $provider = new DataContainerStateProvider();
        $operation = new GetCollection()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $this->assertSame([], $provider->provide($operation));
    }

    public function testProvidesMcpInputForWriteOperations(): void
    {
        $provider = new DataContainerStateProvider();
        $operation = new McpTool(method: 'POST')->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $record = $provider->provide($operation, context: [
            'mcp_data' => [
                'data' => ['headline' => 'Example'],
                'id' => 17,
            ],
        ]);

        $this->assertInstanceOf(DataContainerMcpRecord::class, $record);
        $this->assertSame(['headline' => 'Example'], $record->data);
        $this->assertSame(17, $record->id);
    }

    public function testProvidesAnEmptyCollectionForMcpToolCollectionOperations(): void
    {
        $provider = new DataContainerStateProvider();
        $operation = new McpToolCollection()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $this->assertSame([], $provider->provide($operation));
    }

    public function testReturnsNullWhenNoContaoTableIsConfigured(): void
    {
        $provider = new DataContainerStateProvider();

        $this->assertNull($provider->provide(new Get()));
    }
}
