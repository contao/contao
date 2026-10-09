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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\MainControllerResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\Symfony\Routing\ApiLoader;
use Contao\ApiBundle\ApiPlatform\Metadata\VirtualFilesystemResourceMetadataCollectionFactory;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\ApiBundle\Serializer\VirtualFilesystemMetadataNormalizationHandler;
use Contao\CoreBundle\File\UploadSizeProvider;
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
        $metadata = $operations['contao_api_files_metadata'];
        $move = $operations['contao_api_files_move'];

        $this->assertInstanceOf(GetCollection::class, $collection);
        $this->assertSame('/files', $collection->getUriTemplate());
        $this->assertFalse($collection->getPaginationEnabled());
        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_provider', $collection->getProvider());

        $this->assertInstanceOf(Get::class, $get);
        $this->assertSame('/files/{pathOrUuid}', $get->getUriTemplate());
        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_provider', $get->getProvider());

        $this->assertInstanceOf(Put::class, $upload);
        $this->assertFalse($upload->canRead());
        $this->assertFalse($upload->canDeserialize());

        $uploadOpenApi = $upload->getOpenapi();
        $this->assertInstanceOf(OpenApiOperation::class, $uploadOpenApi);
        $this->assertSame('Upload a file', $uploadOpenApi->getSummary());
        $this->assertInstanceOf(RequestBody::class, $uploadOpenApi->getRequestBody());
        $this->assertSame('The raw contents of the file.', $uploadOpenApi->getRequestBody()->getDescription());

        $uploadSchema = $uploadOpenApi->getRequestBody()->getContent()['application/octet-stream']->getSchema();
        $this->assertSame(1234, $uploadSchema['maxLength']);
        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_processor', $upload->getProcessor());

        $this->assertInstanceOf(Post::class, $metadata);
        $this->assertSame('/files_operations/metadata', $metadata->getUriTemplate());
        $this->assertFalse($metadata->getInput());
        $this->assertFalse($metadata->canRead());
        $this->assertFalse($metadata->canDeserialize());

        $metadataOpenApi = $metadata->getOpenapi();
        $this->assertInstanceOf(OpenApiOperation::class, $metadataOpenApi);
        $this->assertSame('Update file metadata', $metadataOpenApi->getSummary());
        $this->assertInstanceOf(RequestBody::class, $metadataOpenApi->getRequestBody());
        $this->assertSame('The file path or UUID and metadata values to update.', $metadataOpenApi->getRequestBody()->getDescription());

        $metadataSchema = $metadataOpenApi->getRequestBody()->getContent()['application/json']->getSchema();
        $this->assertSame(['path', 'data'], $metadataSchema['required']);
        $this->assertSame('The file path or UUID.', $metadataSchema['properties']['path']['description']);
        $this->assertSame('object', $metadataSchema['properties']['data']['type']);
        $this->assertSame(200, $metadata->getStatus());
        $this->assertSame("is_granted('ROLE_USER') and is_granted('contao_user.fop.f2')", $metadata->getSecurity());
        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_processor', $metadata->getProcessor());

        $this->assertInstanceOf(Post::class, $move);
        $this->assertSame(VirtualFilesystemMove::class, $move->getInput());
        $this->assertSame(200, $move->getStatus());
    }

    public function testDocumentsPathOrUuidAndMapsItToTheFileIdentifier(): void
    {
        $factory = $this->createFactory($this->createStub(ResourceMetadataCollectionFactoryInterface::class));
        $operations = iterator_to_array($factory->create(VirtualFilesystemItem::class)[0]->getOperations());

        foreach (['contao_api_files_get', 'contao_api_files_upload'] as $name) {
            $operation = $operations[$name];
            $this->assertSame('/files/{pathOrUuid}', $operation->getUriTemplate());
            $this->assertSame(['pathOrUuid' => '.+'], $operation->getRequirements());
            $link = $operation->getUriVariables()['pathOrUuid'];
            $this->assertSame(['path'], $link->getIdentifiers());
            $this->assertSame(VirtualFilesystemItem::class, $link->getFromClass());
            $this->assertSame('The file path or UUID.', $link->getDescription());
        }
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
        $this->assertSame('/files/images/example.jpg', $generator->generate('contao_api_files_get', ['pathOrUuid' => 'images/example.jpg']));
        $this->assertSame('/files_operations/move', $generator->generate('contao_api_files_move'));
        $this->assertSame('/files_operations/metadata', $generator->generate('contao_api_files_metadata'));
        $this->assertSame('backend', $routes->get('contao_api_files_get')->getDefault('_scope'));

        $context = new RequestContext();
        $context->setMethod('POST');
        $this->assertSame('contao_api_files_move', new UrlMatcher($routes, $context)->match('/files_operations/move')['_route']);
        $this->assertSame('contao_api_files_metadata', new UrlMatcher($routes, $context)->match('/files_operations/metadata')['_route']);

        $context->setMethod('GET');
        $match = new UrlMatcher($routes, $context)->match('/files/move');
        $this->assertSame('contao_api_files_get', $match['_route']);
        $this->assertSame('move', $match['pathOrUuid']);
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

        return new VirtualFilesystemResourceMetadataCollectionFactory($decorated, $normalizer, $this->createUploadSizeProvider());
    }

    private function createUploadSizeProvider(int $maximumUploadSize = 1234): UploadSizeProvider
    {
        return new UploadSizeProvider($maximumUploadSize, $maximumUploadSize);
    }
}
