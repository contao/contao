<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\OpenApi;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Schema;
use ApiPlatform\OpenApi\OpenApi;
use Contao\ApiBundle\ApiPlatform\OpenApi\DataContainerOpenApiFactory;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProvider;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\Controller;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;

final class DataContainerOpenApiFactoryTest extends ContaoTestCase
{
    private array|null $widgets = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL']['text'] = TextField::class;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }

        parent::tearDown();
    }

    public function testGeneratesOpenApiFromResourceMetadata(): void
    {
        $controllerAdapter = $this->createAdapterMock(['loadDataContainer']);
        $controllerAdapter
            ->expects($this->once())
            ->method('loadDataContainer')
            ->with('tl_content')
            ->willReturnCallback(
                static function (): void {
                    $GLOBALS['TL_DCA']['tl_content']['fields'] = [
                        'id' => [
                            'sql' => ['type' => 'int', 'unsigned' => true, 'notnull' => true, 'default' => 0],
                        ],
                        'title' => [
                            'inputType' => 'text',
                            'sql' => ['type' => 'varchar', 'length' => 255, 'default' => ''],
                            'eval' => [
                                'mandatory' => true,
                            ],
                        ],
                    ];
                },
            )
        ;

        $framework = $this->createContaoFrameworkStub([Controller::class => $controllerAdapter]);
        $schemaFactory = new DataContainerSchemaFactory($framework, new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]));

        $resourceMetadataCollectionFactory = new class($this->createResourceMetadataCollection()) implements ResourceMetadataCollectionFactoryInterface {
            public function __construct(private readonly ResourceMetadataCollection $collection)
            {
            }

            public function create(string $resourceClass): ResourceMetadataCollection
            {
                return $this->collection;
            }
        };

        $decorated = new class($this->createOpenApi()) implements OpenApiFactoryInterface {
            public function __construct(private readonly OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        $factory = new DataContainerOpenApiFactory($decorated, $resourceMetadataCollectionFactory, $schemaFactory, '/_api');
        $openApi = $factory();

        $schemas = $openApi->getComponents()->getSchemas();
        $this->assertInstanceOf(\ArrayObject::class, $schemas);
        $this->assertArrayHasKey('dc_tl_content', $schemas->getArrayCopy());

        $componentSchema = $schemas['dc_tl_content'];
        $this->assertInstanceOf(Schema::class, $componentSchema);
        $this->assertSame('object', $componentSchema['type']);

        $collectionPathItem = $openApi->getPaths()->getPath('/_api/backend/dc/tl_content');
        $this->assertInstanceOf(PathItem::class, $collectionPathItem);

        $getCollection = $collectionPathItem->getGet();
        $this->assertInstanceOf(Response::class, $getCollection->getResponses()['200']);
        $collectionSchema = $getCollection->getResponses()['200']->getContent()['application/json']->getSchema();
        $this->assertSame('array', $collectionSchema['type']);
        $this->assertSame('#/components/schemas/dc_tl_content', $collectionSchema['items']['$ref']);

        $post = $collectionPathItem->getPost();
        $this->assertInstanceOf(RequestBody::class, $post->getRequestBody());
        $this->assertSame('#/components/schemas/dc_tl_content_create', $post->getRequestBody()->getContent()['application/json']->getSchema()['$ref']);
        $this->assertSame('#/components/schemas/dc_tl_content', $post->getResponses()['201']->getContent()['application/json']->getSchema()['$ref']);

        $itemPathItem = $openApi->getPaths()->getPath('/_api/backend/dc/tl_content/{id}');
        $this->assertInstanceOf(PathItem::class, $itemPathItem);
        $this->assertSame('#/components/schemas/dc_tl_content', $itemPathItem->getGet()->getResponses()['200']->getContent()['application/json']->getSchema()['$ref']);
        $this->assertSame('#/components/schemas/dc_tl_content_update', $itemPathItem->getPatch()->getRequestBody()->getContent()['application/merge-patch+json']->getSchema()['$ref']);
        $this->assertSame('#/components/schemas/dc_tl_content', $itemPathItem->getPatch()->getResponses()['200']->getContent()['application/json']->getSchema()['$ref']);
        $this->assertSame(204, (int) array_key_first($itemPathItem->getDelete()->getResponses()));

        $move = $openApi->getPaths()->getPath('/_api/backend/dc/tl_content/{id}/move')->getPost();
        $this->assertSame(['target'], $schemas['dc_tl_content_move']['required']);
        $this->assertSame(['first', 'last', 'after'], $schemas['dc_tl_content_move']['properties']['position']['enum']);
        $this->assertArrayHasKey(200, $move->getResponses());
        $this->assertArrayNotHasKey(201, $move->getResponses());
        $this->assertSame('#/components/schemas/dc_tl_content_move', $move->getRequestBody()->getContent()['application/json']->getSchema()['$ref']);

        $link = $itemPathItem->getGet()->getResponses()['200']->getLinks()['move'];
        $this->assertSame($move->getOperationId(), $link->getOperationId());
        $this->assertSame(['id' => '$response.body#/id'], $link->getParameters()->getArrayCopy());
        $this->assertArrayNotHasKey('id', $schemas['dc_tl_content_create']['properties']);
        $this->assertArrayNotHasKey('required', $schemas['dc_tl_content_update']);
        $this->assertSame('Unrelated resource', $openApi->getPaths()->getPath('/unrelated')->getGet()->getSummary());
    }

    public function testDoesNotLinkToAnUnsupportedMoveOperation(): void
    {
        $resource = new ApiResource(
            shortName: 'Content',
            operations: ['get' => new Get(uriTemplate: '/dc/tl_content/{id}')],
            extraProperties: ['contao' => ['table' => 'tl_content', 'schema_path' => 'dc/tl_content']],
        );

        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, [$resource]))
        ;

        $decorated = $this->createStub(OpenApiFactoryInterface::class);
        $decorated
            ->method('__invoke')
            ->willReturn(new OpenApi(new Info('API', '1'), [], new Paths()))
        ;

        $framework = $this->createContaoFrameworkStub([Controller::class => $this->createAdapterStub(['loadDataContainer'])]);
        $factory = new DataContainerOpenApiFactory($decorated, $metadata, new DataContainerSchemaFactory($framework, new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))])), '/_api');
        $openApi = $factory();

        $this->assertNull($openApi->getPaths()->getPath('/_api/dc/tl_content/{id}')->getGet()->getResponses()['200']->getLinks());
        $this->assertNull($openApi->getPaths()->getPath('/_api/dc/tl_content/{id}/move'));
    }

    public function testDoesNotDoublePrefixAlreadyPrefixedPaths(): void
    {
        $factory = new DataContainerOpenApiFactory(
            new class() implements OpenApiFactoryInterface {
                public function __invoke(array $context = []): OpenApi
                {
                    return new OpenApi(new Info('Contao API', '1.0.0'), [], new Paths());
                }
            },
            new class() implements ResourceMetadataCollectionFactoryInterface {
                public function create(string $resourceClass): ResourceMetadataCollection
                {
                    return new ResourceMetadataCollection(DataContainerRecord::class, []);
                }
            },
            new DataContainerSchemaFactory($this->createContaoFrameworkStub(), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))])),
            '/_api',
        );

        $this->assertSame('/_api/backend/dc/tl_content', $factory->getPathForDataContainerResource('/_api/backend/dc/tl_content'));
    }

    private function createResourceMetadataCollection(): ResourceMetadataCollection
    {
        $operations = new Operations([
            'move' => new Post(uriTemplate: '/backend/dc/tl_content/{id}/move', extraProperties: ['contao' => ['action' => 'move']]),
            'get_collection' => new GetCollection()
                ->withClass(DataContainerRecord::class)
                ->withShortName('Content')
                ->withUriTemplate('/_api/backend/dc/tl_content'),
            'get' => new Get()
                ->withClass(DataContainerRecord::class)
                ->withShortName('Content')
                ->withUriTemplate('/_api/backend/dc/tl_content/{id}'),
            'post' => new Post()
                ->withClass(DataContainerRecord::class)
                ->withShortName('Content')
                ->withUriTemplate('/_api/backend/dc/tl_content'),
            'patch' => new Patch()
                ->withClass(DataContainerRecord::class)
                ->withShortName('Content')
                ->withUriTemplate('/_api/backend/dc/tl_content/{id}'),
            'delete' => new Delete()
                ->withClass(DataContainerRecord::class)
                ->withShortName('Content')
                ->withUriTemplate('/_api/backend/dc/tl_content/{id}'),
        ]);

        $resource = new ApiResource()
            ->withClass(DataContainerRecord::class)
            ->withShortName('Content')
            ->withRoutePrefix('/_api/backend/dc/tl_content')
            ->withProvider(DataContainerStateProvider::class)
            ->withProcessor(DataContainerStateProcessor::class)
            ->withExtraProperties([
                'contao' => [
                    'table' => 'tl_content',
                    'schema_path' => DataContainerOpenApiFactory::getSchemaPath('tl_content'),
                ],
            ])
            ->withOperations($operations)
        ;

        return new ResourceMetadataCollection(DataContainerRecord::class, [$resource]);
    }

    private function createOpenApi(): OpenApi
    {
        $paths = new Paths();
        $paths->addPath('/_api/backend/dc/tl_content', new PathItem(post: new Operation(summary: 'Generic generated operation')));
        $paths->addPath('/unrelated', new PathItem(get: new Operation(summary: 'Unrelated resource')));

        return new OpenApi(new Info('Contao API', '1.0.0'), [], $paths);
    }
}
