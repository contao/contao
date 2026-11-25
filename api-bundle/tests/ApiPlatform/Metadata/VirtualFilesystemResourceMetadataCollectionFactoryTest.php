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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\Resource\Factory\MainControllerResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\Serializer\SerializerContextBuilder;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Routing\ApiLoader;
use ApiPlatform\Symfony\Routing\IriConverter;
use Contao\ApiBundle\ApiPlatform\Metadata\VirtualFilesystemResourceMetadataCollectionFactory;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\ApiBundle\Serializer\VirtualFilesystemMetadataNormalizationHandler;
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

final class VirtualFilesystemResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testIdentifiesTheVfsForReadOperations(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory());
        $resource = iterator_to_array($factory->create(VirtualFilesystemItem::class))[0];
        $operations = iterator_to_array($resource->getOperations());

        $this->assertSame('files', $operations['contao_api_api_files_get_by_path']->getExtraProperties()['contao']['relation_filesystem']);
        $this->assertSame('api_files', $operations['contao_api_api_files_get_by_path']->getExtraProperties()['contao']['filesystem']);
    }

    public function testOnlyExposesTaggedFilesystems(): void
    {
        $resources = $this->createFactory($this->createEmptyMetadataFactory(), [
            'private' => [],
            'disabled' => ['api_exposure' => false, 'api_path' => '/disabled'],
        ])->create(VirtualFilesystemItem::class);

        $this->assertCount(1, $resources);
        $this->assertSame('api_files', $resources[0]->getExtraProperties()['contao']['filesystem']);
        $this->assertSame('files', $resources[0]->getExtraProperties()['contao']['relation_filesystem']);
    }

    public function testExposesAnotherFilesystemAlongsideFiles(): void
    {
        $factory = $this->createTestFilesystemFactory();
        $resources = $factory->create(VirtualFilesystemItem::class);

        $this->assertCount(2, $resources);
        $this->assertCount(2, $factory->create(VirtualFilesystemItem::class));
        $this->assertSame('files', $resources[0]->getExtraProperties()['contao']['relation_filesystem']);
        $this->assertSame('test_files', $resources[1]->getExtraProperties()['contao']['relation_filesystem']);

        $operations = iterator_to_array($resources[1]->getOperations());
        $this->assertSame('/fixtures/test-files/by-path/{path}', $operations['contao_api_test_files_get_by_path']->getUriTemplate());

        foreach ($operations as $operation) {
            $this->assertSame('test_files', $operation->getExtraProperties()['contao']['relation_filesystem']);

            $expected = "is_granted('ROLE_ADMIN')";
            $this->assertSame($expected, $operation->getSecurity());
        }

        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_provider', $operations['contao_api_test_files_get_by_path']->getProvider());
        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_processor', $operations['contao_api_test_files_upload_by_path']->getProcessor());

        $generator = new UrlGenerator($this->createApiLoader($factory)->load(null), new RequestContext());
        $this->assertSame('/files/by-path/test.txt', $generator->generate('contao_api_api_files_get_by_path', ['path' => 'test.txt']));
        $this->assertSame('/fixtures/test-files/by-path/test.zip', $generator->generate('contao_api_test_files_get_by_path', ['path' => 'test.zip']));
        $this->assertSame('/fixtures/test-files_operations/move', $generator->generate('contao_api_test_files_move'));
    }

    public function testOverridesSecurityForEveryOperation(): void
    {
        $tags = ['api_exposure' => true, 'api_path' => '/assets'];

        foreach (['get_collection', 'get_by_path', 'get_by_uuid', 'upload_by_path', 'upload_by_uuid', 'move', 'metadata'] as $name) {
            $tags['api_operation_attributes'][$name]['security'] = "is_granted('".$name."')";
        }

        // Operation-specific attributes take precedence regardless of tag order.
        $tags['api_operation_attributes']['*']['security'] = "is_granted('ROLE_USER')";
        $factory = $this->createFactory($this->createEmptyMetadataFactory(), ['assets' => $tags]);
        $operations = iterator_to_array($factory->create(VirtualFilesystemItem::class)[1]->getOperations());

        foreach (['get_collection', 'get_by_path', 'get_by_uuid', 'upload_by_path', 'upload_by_uuid', 'move', 'metadata'] as $name) {
            $this->assertSame("is_granted('".$name."')", $operations['contao_api_assets_'.$name]->getSecurity());
        }
    }

    public function testConfiguresPathAndUuidOperationAttributesIndependently(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory(), [
            'assets' => [
                'api_exposure' => true,
                'api_path' => '/assets',
                'api_operation_attributes' => [
                    'get_by_path' => ['security' => "is_granted('READ_PATH')"],
                    'get_by_uuid' => ['security' => "is_granted('READ_UUID')"],
                    'upload_by_path' => ['security' => "is_granted('WRITE_ASSET')"],
                    'upload_by_uuid' => ['security' => "is_granted('REPLACE_ASSET')"],
                ],
            ],
        ]);

        $operations = iterator_to_array($factory->create(VirtualFilesystemItem::class)[1]->getOperations());

        $this->assertSame("is_granted('READ_PATH')", $operations['contao_api_assets_get_by_path']->getSecurity());
        $this->assertSame("is_granted('READ_UUID')", $operations['contao_api_assets_get_by_uuid']->getSecurity());
        $this->assertSame("is_granted('WRITE_ASSET')", $operations['contao_api_assets_upload_by_path']->getSecurity());
        $this->assertSame("is_granted('REPLACE_ASSET')", $operations['contao_api_assets_upload_by_uuid']->getSecurity());
    }

    public function testMatchesUuidShapedPathsAndUuidRoutesSeparately(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory());
        $routes = $this->createApiLoader($factory)->load(null);
        $matcher = new UrlMatcher($routes, new RequestContext());
        $uuid = '171bb68d-0094-4f6c-88f5-9b83c0d01521';

        $path = $matcher->match('/files/by-path/'.$uuid);
        $identity = $matcher->match('/files/by-uuid/'.$uuid);

        $this->assertSame('contao_api_api_files_get_by_path', $path['_route']);
        $this->assertSame($uuid, $path['path']);
        $this->assertSame('contao_api_api_files_get_by_uuid', $identity['_route']);
        $this->assertSame($uuid, $identity['uuid']);
    }

    public function testConfiguresOtherOperationAttributesAndPreservesTheirTypes(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory(), [
            'assets' => [
                'api_exposure' => true,
                'api_path' => '/assets',
                'api_operation_attributes' => [
                    '*' => ['status' => 202, 'extraProperties' => ['custom' => true]],
                    'upload_by_path' => ['status' => 201, 'validationContext' => ['groups' => ['upload']]],
                    'get_by_path' => ['security' => null, 'description' => 'Read an asset.'],
                    'get_collection' => [
                        'paginationEnabled' => true,
                        'paginationItemsPerPage' => 10,
                        'security' => "is_granted('ROLE_ADMIN')",
                    ],
                ],
                'label' => 'Assets',
            ],
        ]);

        $operations = iterator_to_array($factory->create(VirtualFilesystemItem::class)[1]->getOperations());
        $get = $operations['contao_api_assets_get_by_path'];
        $collection = $operations['contao_api_assets_get_collection'];
        $upload = $operations['contao_api_assets_upload_by_path'];

        $this->assertSame(202, $get->getStatus());
        $this->assertSame(201, $upload->getStatus());
        $this->assertNull($get->getSecurity());
        $this->assertSame('Read an asset.', $get->getDescription());
        $this->assertTrue($collection->getPaginationEnabled());
        $this->assertSame(10, $collection->getPaginationItemsPerPage());
        $this->assertSame("is_granted('ROLE_ADMIN')", $collection->getSecurity());
        $this->assertSame(['groups' => ['upload']], $upload->getValidationContext());
        $this->assertTrue($get->getExtraProperties()['custom']);
        $this->assertSame('assets', $get->getExtraProperties()['contao']['filesystem']);
        $this->assertSame('metadata', $operations['contao_api_assets_metadata']->getExtraProperties()['contao']['operation']);
    }

    public function testRejectsUnknownOperationAttributes(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory(), [
            'assets' => [
                'api_exposure' => true,
                'api_path' => '/assets',
                'api_operation_attributes' => ['upload_by_path' => ['securty' => "is_granted('ROLE_ADMIN')"]],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('securty');

        $factory->create(VirtualFilesystemItem::class);
    }

    public function testRejectsUnknownOperationNames(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory(), [
            'assets' => [
                'api_exposure' => true,
                'api_path' => '/assets',
                'api_operation_attributes' => ['uplod' => ['security' => "is_granted('ROLE_ADMIN')"]],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('uplod');

        $factory->create(VirtualFilesystemItem::class);
    }

    public function testGeneratesItemIrisForEachFilesystem(): void
    {
        $factory = $this->createRoutingMetadataFactory();
        $converter = $this->createIriConverter($factory);
        $item = new VirtualFilesystemItem('archive.zip', 'archive.zip', true, null, null, null, []);
        $contextBuilder = new SerializerContextBuilder($factory);

        foreach ($factory->create(VirtualFilesystemItem::class) as $resource) {
            $expected = 'files' === $resource->getExtraProperties()['contao']['relation_filesystem'] ? '/files/by-path/archive.zip' : '/fixtures/test-files/by-path/archive.zip';

            foreach ($resource->getOperations() as $operation) {
                $context = $contextBuilder->createFromRequest(
                    Request::create('/'),
                    true,
                    [
                        'resource_class' => VirtualFilesystemItem::class,
                        'operation' => $operation,
                    ],
                );

                if ($operation instanceof GetCollection) {
                    // Collection items use the read operation selected by itemUriTemplate.
                    $context['item_uri_template'] = $operation->getItemUriTemplate();
                    unset($context['operation']);
                }

                $this->assertSame($expected, $converter->getIriFromResource($item, operation: $context['operation'] ?? null, context: $context));
            }
        }
    }

    public function testBuildsTheVirtualFilesystemResource(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory());
        $resources = iterator_to_array($factory->create(VirtualFilesystemItem::class));
        $this->assertCount(1, $resources);

        $resource = $resources[0];
        $this->assertSame('File', $resource->getShortName());
        $this->assertNull($resource->getSecurity());

        $operations = iterator_to_array($resource->getOperations());
        $collection = $operations['contao_api_api_files_get_collection'];
        $get = $operations['contao_api_api_files_get_by_path'];
        $upload = $operations['contao_api_api_files_upload_by_path'];
        $metadata = $operations['contao_api_api_files_metadata'];
        $move = $operations['contao_api_api_files_move'];

        $this->assertInstanceOf(GetCollection::class, $collection);
        $this->assertSame('/files', $collection->getUriTemplate());
        $this->assertFalse($collection->getPaginationEnabled());
        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_provider', $collection->getProvider());

        $this->assertInstanceOf(Get::class, $get);
        $this->assertSame('/files/by-path/{path}', $get->getUriTemplate());
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
        $this->assertSame(['data'], $metadataSchema['required']);
        $this->assertSame([['required' => ['path']], ['required' => ['uuid']]], $metadataSchema['oneOf']);
        $this->assertSame('uuid', $metadataSchema['properties']['uuid']['format']);
        $this->assertSame('object', $metadataSchema['properties']['data']['type']);
        $this->assertSame(200, $metadata->getStatus());
        $this->assertSame("is_granted('contao_user.fop.f2')", $metadata->getSecurity());
        $this->assertSame('contao_api.api_platform.virtual_filesystem_state_processor', $metadata->getProcessor());

        $this->assertInstanceOf(Post::class, $move);
        $this->assertSame(VirtualFilesystemMove::class, $move->getInput());
        $this->assertSame(200, $move->getStatus());
    }

    public function testOnlyFileMetadataRequiresTheRenamePermission(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory());
        $operations = $factory->create(VirtualFilesystemItem::class)[0]->getOperations();

        foreach ($operations as $name => $operation) {
            $expected = null;

            if ('contao_api_api_files_metadata' === $name) {
                $expected = "is_granted('contao_user.fop.f2')";
            }

            $this->assertSame($expected, $operation->getSecurity());
        }
    }

    public function testDocumentsLiteralPathsAndMapsThemToTheFileIdentifier(): void
    {
        $factory = $this->createFactory($this->createEmptyMetadataFactory());
        $operations = iterator_to_array($factory->create(VirtualFilesystemItem::class)[0]->getOperations());

        foreach (['contao_api_api_files_get_by_path', 'contao_api_api_files_upload_by_path'] as $name) {
            $operation = $operations[$name];
            $this->assertSame('/files/by-path/{path}', $operation->getUriTemplate());
            $this->assertSame(['path' => '.+'], $operation->getRequirements());

            $link = $operation->getUriVariables()['path'];
            $this->assertSame(['path'], $link->getIdentifiers());
            $this->assertSame(VirtualFilesystemItem::class, $link->getFromClass());
            $this->assertSame('The literal file path.', $link->getDescription());
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
        $factory = $this->createFactory($this->createEmptyMetadataFactory());
        $routes = $this->createApiLoader($factory)->load(null);
        $generator = new UrlGenerator($routes, new RequestContext());

        $this->assertSame('/files', $generator->generate('contao_api_api_files_get_collection'));
        $this->assertSame('/files/by-path/images/example.jpg', $generator->generate('contao_api_api_files_get_by_path', ['path' => 'images/example.jpg']));
        $this->assertSame('/files_operations/move', $generator->generate('contao_api_api_files_move'));
        $this->assertSame('/files_operations/metadata', $generator->generate('contao_api_api_files_metadata'));
        $this->assertSame('backend', $routes->get('contao_api_api_files_get_by_path')->getDefault('_scope'));

        $context = new RequestContext();
        $context->setMethod('POST');

        $this->assertSame('contao_api_api_files_move', new UrlMatcher($routes, $context)->match('/files_operations/move')['_route']);
        $this->assertSame('contao_api_api_files_metadata', new UrlMatcher($routes, $context)->match('/files_operations/metadata')['_route']);

        $context->setMethod('GET');
        $match = new UrlMatcher($routes, $context)->match('/files/by-path/move');
        $this->assertSame('contao_api_api_files_get_by_path', $match['_route']);
        $this->assertSame('move', $match['path']);
    }

    private function createRoutingMetadataFactory(): ResourceMetadataCollectionFactoryInterface
    {
        $properties = $this->createStub(PropertyMetadataFactoryInterface::class);
        $properties
            ->method('create')
            ->willReturn(new ApiProperty(identifier: true))
        ;

        $names = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $names
            ->method('create')
            ->willReturn(new PropertyNameCollection(['path']))
        ;

        $classes = $this->createStub(ResourceClassResolverInterface::class);
        $classes
            ->method('isResourceClass')
            ->willReturn(true)
        ;

        $links = new LinkFactory($names, $properties, $classes);

        return new UriTemplateResourceMetadataCollectionFactory($links, new UnderscorePathSegmentNameGenerator(), $this->createTestFilesystemFactory());
    }

    private function createIriConverter(ResourceMetadataCollectionFactoryInterface $factory): IriConverter
    {
        $names = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $names
            ->method('create')
            ->willReturn(new ResourceNameCollection([VirtualFilesystemItem::class]))
        ;

        $classes = $this->createStub(ResourceClassResolverInterface::class);
        $classes
            ->method('isResourceClass')
            ->willReturn(true)
        ;
        $classes
            ->method('getResourceClass')
            ->willReturn(VirtualFilesystemItem::class)
        ;

        $identifiers = $this->createStub(IdentifiersExtractorInterface::class);
        $identifiers
            ->method('getIdentifiersFromItem')
            ->willReturnCallback(static fn (VirtualFilesystemItem $item): array => ['path' => $item->path])
        ;

        $generator = new UrlGenerator($this->createApiLoader($factory)->load(null), new RequestContext());

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback($generator->generate(...))
        ;

        return new IriConverter(
            $this->createStub(ProviderInterface::class),
            $router,
            $identifiers,
            $classes,
            $factory,
            operationMetadataFactory: new OperationMetadataFactory($names, $factory),
        );
    }

    private function createTestFilesystemFactory(): VirtualFilesystemResourceMetadataCollectionFactory
    {
        return $this->createFactory($this->createEmptyMetadataFactory(), [
            'test_files' => ['api_exposure' => true, 'api_path' => '/fixtures/test-files', 'api_operation_attributes' => ['*' => ['security' => "is_granted('ROLE_ADMIN')"]]],
        ]);
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

    private function createEmptyMetadataFactory(): ResourceMetadataCollectionFactoryInterface
    {
        $factory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $factory
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(VirtualFilesystemItem::class))
        ;

        return $factory;
    }

    private function createFactory(ResourceMetadataCollectionFactoryInterface $decorated, array $tags = []): VirtualFilesystemResourceMetadataCollectionFactory
    {
        $normalizer = new SchemaAwareObjectNormalizer(new Validator(), [new VirtualFilesystemMetadataNormalizationHandler()]);

        return new VirtualFilesystemResourceMetadataCollectionFactory(
            $decorated,
            $normalizer,
            $this->createUploadSizeProvider(),
            new VirtualFilesystemRegistry(
                new ServiceLocator([]),
                [
                    'files' => [],
                    'api_files' => [
                        'api_exposure' => true,
                        'api_path' => '/files',
                        'api_relation_filesystem' => 'files',
                        'api_operation_attributes' => [
                            'metadata' => ['security' => "is_granted('contao_user.fop.f2')"],
                        ],
                    ],
                ] + $tags,
            ),
        );
    }

    private function createUploadSizeProvider(int $maximumUploadSize = 1234): UploadSizeProvider
    {
        return new UploadSizeProvider($maximumUploadSize, $maximumUploadSize);
    }
}
