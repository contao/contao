<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\Metadata\DataContainerResourceMetadataCollectionFactory;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\TestCase;

final class DataContainerResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testBuildsMetadataForTheHardcodedContentResource(): void
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);

        $factory = new DataContainerResourceMetadataCollectionFactory($decorated, '/_api', 'backend/dc');
        $collection = $factory->create(DataContainerRecord::class);

        $this->assertCount(1, $collection);

        $resource = $collection[0];
        $this->assertInstanceOf(ApiResource::class, $resource);
        $this->assertSame(DataContainerRecord::class, $resource->getClass());
        $this->assertSame('Content', $resource->getShortName());
        $this->assertSame('/_api/backend/dc/tl_content', $resource->getRoutePrefix());
        $this->assertSame('tl_content', $resource->getExtraProperties()['contao']['table']);

        $operations = $resource->getOperations();
        $this->assertInstanceOf(Operations::class, $operations);
        $this->assertCount(5, $operations);

        $operations = iterator_to_array($operations);

        $this->assertOperation($operations['get_collection'], GetCollection::class, '/_api/backend/dc/tl_content');
        $this->assertOperation($operations['get'], Get::class, '/_api/backend/dc/tl_content/{id}');
        $this->assertOperation($operations['post'], Post::class, '/_api/backend/dc/tl_content');
        $this->assertOperation($operations['patch'], Patch::class, '/_api/backend/dc/tl_content/{id}');
        $this->assertOperation($operations['delete'], Delete::class, '/_api/backend/dc/tl_content/{id}');
    }

    public function testDelegatesForNonDataContainerResources(): void
    {
        $collection = new ResourceMetadataCollection('App\\Entity\\Foo');
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->with('App\\Entity\\Foo')
            ->willReturn($collection)
        ;

        $factory = new DataContainerResourceMetadataCollectionFactory($decorated, '/_api', 'backend/dc');

        $this->assertSame($collection, $factory->create('App\\Entity\\Foo'));
    }

    private function assertOperation(object $operation, string $expectedClass, string $expectedUriTemplate): void
    {
        $this->assertInstanceOf($expectedClass, $operation);
        $this->assertSame(DataContainerRecord::class, $operation->getClass());
        $this->assertSame('Content', $operation->getShortName());
        $this->assertSame($expectedUriTemplate, $operation->getUriTemplate());
    }
}
