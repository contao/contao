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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\MainControllerResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Symfony\Routing\ApiLoader;
use Contao\ApiBundle\ApiPlatform\Metadata\VirtualFilesystemResourceMetadataCollectionFactory;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemStateProvider;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\ApiBundle\Serializer\VirtualFilesystemMetadataNormalizationHandler;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

final class VirtualFilesystemResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testBuildsTheVirtualFilesystemResource(): void
    {
        $factory = $this->createFactory($this->createStub(ResourceMetadataCollectionFactoryInterface::class));
        $resources = iterator_to_array($factory->create(VirtualFilesystemItem::class));
        $this->assertCount(1, $resources);

        $resource = $resources[0];
        $this->assertSame('File', $resource->getShortName());
        $this->assertSame("is_granted('ROLE_USER')", (string) $resource->getSecurity());

        $operations = iterator_to_array($resource->getOperations());
        $collection = $operations['contao_api_files_get_collection'];
        $get = $operations['contao_api_files_get'];
        $upload = $operations['contao_api_files_upload'];
        $metadata = $operations['contao_api_files_update_metadata'];
        $move = $operations['contao_api_files_move'];

        $this->assertInstanceOf(GetCollection::class, $collection);
        $this->assertSame('/files', $collection->getUriTemplate());
        $this->assertFalse($collection->getPaginationEnabled());
        $this->assertSame(VirtualFilesystemStateProvider::class, $collection->getProvider());

        $this->assertInstanceOf(Get::class, $get);
        $this->assertSame('/files/{path}', $get->getUriTemplate());
        $this->assertSame(VirtualFilesystemStateProvider::class, $get->getProvider());

        $this->assertInstanceOf(Put::class, $upload);
        $this->assertFalse($upload->canRead());
        $this->assertFalse($upload->canDeserialize());
        $this->assertSame(VirtualFilesystemStateProcessor::class, $upload->getProcessor());

        $this->assertInstanceOf(Patch::class, $metadata);
        $this->assertSame('/files/{path}', $metadata->getUriTemplate());
        $this->assertFalse($metadata->getInput());
        $this->assertFalse($metadata->canRead());
        $this->assertFalse($metadata->canDeserialize());
        $this->assertSame(200, $metadata->getStatus());
        $this->assertSame("is_granted('ROLE_USER') and is_granted('contao_user.fop.f2')", $metadata->getSecurity());
        $this->assertSame(VirtualFilesystemStateProcessor::class, $metadata->getProcessor());

        $this->assertInstanceOf(Post::class, $move);
        $this->assertSame(VirtualFilesystemMove::class, $move->getInput());
        $this->assertSame(200, $move->getStatus());
    }

    public function testDelegatesOtherResources(): void
    {
        $collection = new ResourceMetadataCollection('App\\Entity\\Foo');
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->with('App\\Entity\\Foo')
            ->willReturn($collection)
        ;

        $factory = $this->createFactory($decorated);

        $this->assertSame($collection, $factory->create('App\\Entity\\Foo'));
    }

    public function testGeneratesRoutesForNestedPaths(): void
    {
        $factory = $this->createFactory($this->createStub(ResourceMetadataCollectionFactoryInterface::class));
        $routes = $this->createApiLoader($factory)->load(null);
        $generator = new UrlGenerator($routes, new RequestContext());

        $this->assertSame('/files', $generator->generate('contao_api_files_get_collection'));
        $this->assertSame('/files/images/example.jpg', $generator->generate('contao_api_files_get', ['path' => 'images/example.jpg']));
        $this->assertSame('/files_operations/move', $generator->generate('contao_api_files_move'));
        $this->assertSame('/files/images/example.jpg', $generator->generate('contao_api_files_update_metadata', ['path' => 'images/example.jpg']));
        $this->assertSame('backend', $routes->get('contao_api_files_get')->getDefault('_scope'));

        $context = new RequestContext();
        $context->setMethod('POST');
        $this->assertSame('contao_api_files_move', new UrlMatcher($routes, $context)->match('/files_operations/move')['_route']);

        $context->setMethod('GET');
        $match = new UrlMatcher($routes, $context)->match('/files/move');
        $this->assertSame('contao_api_files_get', $match['_route']);
        $this->assertSame('move', $match['path']);

        $context->setMethod('PATCH');
        $match = new UrlMatcher($routes, $context)->match('/files/images/example.jpg');
        $this->assertSame('contao_api_files_update_metadata', $match['_route']);
        $this->assertSame('images/example.jpg', $match['path']);
    }

    private function createApiLoader(ResourceMetadataCollectionFactoryInterface $factory): ApiLoader
    {
        $names = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $names
            ->method('create')
            ->willReturn(new ResourceNameCollection([VirtualFilesystemItem::class]))
        ;

        $kernel = $this->createStub(KernelInterface::class);
        $kernel
            ->method('locateResource')
            ->willReturn(\dirname(new \ReflectionClass(ApiLoader::class)->getFileName(), 2).'/Bundle/Resources/config/routing')
        ;

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturn(true)
        ;

        return new ApiLoader($kernel, $names, new MainControllerResourceMetadataCollectionFactory($factory), $container, []);
    }

    private function createFactory(ResourceMetadataCollectionFactoryInterface $decorated): VirtualFilesystemResourceMetadataCollectionFactory
    {
        $normalizer = new SchemaAwareObjectNormalizer(new Validator(), [new VirtualFilesystemMetadataNormalizationHandler()]);

        return new VirtualFilesystemResourceMetadataCollectionFactory($decorated, $normalizer);
    }
}
