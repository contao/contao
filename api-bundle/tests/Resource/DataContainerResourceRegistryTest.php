<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Resource\DataContainerResourceRegistry;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;

final class DataContainerResourceRegistryTest extends TestCase
{
    public function testDiscoversResourcesWithoutLoadingTheirSchemas(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $registry = $this->createRegistry($framework);

        $this->assertSame(['resources' => [['resource' => 'tl_news', 'title' => 'News']]], $registry->discover('NEWS'));
        $this->assertCount(200, $registry->discover()['resources']);
        $this->assertSame(['resources' => []], $registry->discover('unknown'));
    }

    public function testDescribesOnlyTheRequestedResource(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $description = $this->createRegistry($framework)->describe('tl_news');

        $this->assertSame('tl_news', $description['resource']);
        $this->assertSame(['list', 'read', 'create', 'update'], $description['operations']);
        $this->assertSame('object', $description['schema']['type']);
    }

    public function testRejectsUnknownResources(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown resource "unknown".');

        $this->createRegistry()->describe('unknown');
    }

    public function testRejectsUnsupportedOperations(): void
    {
        $this->expectException(OperationNotFoundException::class);
        $this->expectExceptionMessage('Resource "tl_news" does not support "delete".');

        $this->createRegistry()->getOperation('tl_news', 'delete');
    }

    public function testResolvesTheResourceOperation(): void
    {
        $operation = $this->createRegistry()->getOperation('tl_news', 'update');

        $this->assertInstanceOf(Patch::class, $operation);
        $this->assertSame('news_patch', $operation->getName());
    }

    private function createRegistry(ContaoFramework|null $framework = null): DataContainerResourceRegistry
    {
        $resources = [];

        for ($i = 0; $i < 200; ++$i) {
            $resources[] = new ApiResource(
                shortName: 0 === $i ? 'News' : 'Resource'.$i,
                operations: [
                    'news_list' => new GetCollection(name: 'news_list'),
                    'news_read' => new Get(name: 'news_read'),
                    'news_post' => new Post(name: 'news_post'),
                    'news_patch' => new Patch(name: 'news_patch'),
                ],
                extraProperties: ['contao' => ['table' => 0 === $i ? 'tl_news' : 'tl_resource_'.$i]],
            );
        }

        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, $resources))
        ;

        return new DataContainerResourceRegistry($metadata, new DataContainerSchemaFactory($framework ?? $this->createStub(ContaoFramework::class)));
    }
}
