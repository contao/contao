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
use Contao\Controller;
use Contao\TestCase\ContaoTestCase;

final class DataContainerOpenApiFactoryTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

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
        $schemaFactory = new DataContainerSchemaFactory($framework);

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
        $this->assertSame('#/components/schemas/dc_tl_content', $post->getRequestBody()->getContent()['application/json']->getSchema()['$ref']);
        $this->assertSame('#/components/schemas/dc_tl_content', $post->getResponses()['201']->getContent()['application/json']->getSchema()['$ref']);

        $itemPathItem = $openApi->getPaths()->getPath('/_api/backend/dc/tl_content/{id}');
        $this->assertInstanceOf(PathItem::class, $itemPathItem);
        $this->assertSame('#/components/schemas/dc_tl_content', $itemPathItem->getGet()->getResponses()['200']->getContent()['application/json']->getSchema()['$ref']);
        $this->assertSame('#/components/schemas/dc_tl_content', $itemPathItem->getPatch()->getRequestBody()->getContent()['application/json']->getSchema()['$ref']);
        $this->assertSame('#/components/schemas/dc_tl_content', $itemPathItem->getPatch()->getResponses()['200']->getContent()['application/json']->getSchema()['$ref']);
        $this->assertSame(204, (int) array_key_first($itemPathItem->getDelete()->getResponses()));
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
            new DataContainerSchemaFactory($this->createContaoFrameworkStub()),
            '/_api',
        );

        $this->assertSame('/_api/backend/dc/tl_content', $factory->getPathForDataContainerResource('/_api/backend/dc/tl_content'));
    }

    private function createResourceMetadataCollection(): ResourceMetadataCollection
    {
        $operations = new Operations([
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
        return new OpenApi(new Info('Contao API', '1.0.0'), [], new Paths());
    }
}
