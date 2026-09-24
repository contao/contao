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
use ApiPlatform\Metadata\Resource\Factory\MainControllerResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Symfony\Routing\ApiLoader;
use Contao\ApiBundle\ApiPlatform\Metadata\DataContainerResourceMetadataCollectionFactory;
use Contao\ApiBundle\ApiPlatform\OpenApi\DataContainerOpenApiFactory;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProvider;
use Contao\ApiBundle\Dto\DataContainerMove;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\DC_File;
use Contao\DC_Table;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;

final class DataContainerResourceMetadataCollectionFactoryTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testBuildsMetadataForAllAvailableDataContainers(): void
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);

        $extendedDcTableClass = new class() extends DC_Table {
            public function __construct()
            {
            }
        }::class;

        $controllerAdapter = $this->createAdapterMock(['loadDataContainer']);
        $controllerAdapter
            ->expects($this->exactly(5))
            ->method('loadDataContainer')
            ->willReturnCallback(
                static function (string $table) use ($extendedDcTableClass): void {
                    $GLOBALS['TL_DCA'][$table]['config'] = match ($table) {
                        'tl_article', 'tl_content' => [
                            'dataContainer' => DC_Table::class,
                        ],
                        'tl_log' => [
                            'dataContainer' => DC_Table::class,
                            'closed' => true,
                            'notDeletable' => true,
                        ],
                        'tl_page' => [
                            'dataContainer' => $extendedDcTableClass,
                            'notDeletable' => true,
                        ],
                        'tl_settings' => [
                            'dataContainer' => DC_File::class,
                        ],
                        default => [],
                    };

                    $GLOBALS['TL_DCA'][$table]['fields'] = [];
                },
            )
        ;

        $framework = $this->createContaoFrameworkStub([Controller::class => $controllerAdapter]);
        $resourceFinder = $this->createResourceFinder(['tl_article', 'tl_content', 'tl_log', 'tl_page', 'tl_settings']);

        $factory = new DataContainerResourceMetadataCollectionFactory($decorated, $framework, $resourceFinder, 'backend/dc');
        $collection = $factory->create(DataContainerRecord::class);

        $this->assertCount(3, $collection);

        $resources = iterator_to_array($collection);

        $this->assertResource($resources[0], 'Article', 'tl_article', '/backend/dc/tl_article', true);
        $this->assertResource($resources[1], 'Content', 'tl_content', '/backend/dc/tl_content', true);
        $this->assertResource($resources[2], 'Page', 'tl_page', '/backend/dc/tl_page', false);
    }

    public function testDelegatesForNonDataContainerResources(): void
    {
        $collection = new ResourceMetadataCollection('App\\Entity\\Foo');
        $framework = $this->createContaoFrameworkStub();
        $resourceFinder = $this->createStub(ResourceFinderInterface::class);

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->with('App\\Entity\\Foo')
            ->willReturn($collection)
        ;

        $factory = new DataContainerResourceMetadataCollectionFactory($decorated, $framework, $resourceFinder, 'backend/dc');

        $this->assertSame($collection, $factory->create('App\\Entity\\Foo'));
    }

    public function testGeneratesDistinctRoutesForEveryTable(): void
    {
        $adapter = $this->createAdapterMock(['loadDataContainer']);
        $adapter
            ->expects($this->exactly(2))
            ->method('loadDataContainer')
            ->willReturnCallback(
                static function (string $table): void {
                    $GLOBALS['TL_DCA'][$table]['config'] = ['dataContainer' => DC_Table::class];
                },
            )
        ;

        $factory = new DataContainerResourceMetadataCollectionFactory(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $this->createContaoFrameworkStub([Controller::class => $adapter]),
            $this->createResourceFinder(['tl_article', 'tl_page']),
            'backend/dc',
        );

        $loader = $this->createApiLoader($factory);
        $routes = $loader->load(null);
        $routes->addPrefix('/custom_api');

        $generator = new UrlGenerator($routes, new RequestContext());

        foreach (['tl_article', 'tl_page'] as $table) {
            $this->assertSame('/custom_api/backend/dc/'.$table.'/42', $generator->generate('contao_api_'.$table.'_patch', ['id' => 42]));
            $this->assertSame('/custom_api/backend/dc/'.$table, $generator->generate('contao_api_'.$table.'_get_collection'));
            $this->assertSame('backend', $routes->get('contao_api_'.$table.'_patch')->getDefault('_scope'));
            $this->assertSame('api_platform.symfony.main_controller', $routes->get('contao_api_'.$table.'_patch')->getDefault('_controller'));
        }
    }

    private function createApiLoader(ResourceMetadataCollectionFactoryInterface $factory): ApiLoader
    {
        $names = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $names
            ->method('create')
            ->willReturn(new ResourceNameCollection([DataContainerRecord::class]))
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

    private function assertResource(ApiResource $resource, string $expectedShortName, string $expectedTable, string $expectedRoutePrefix, bool $deletable): void
    {
        $this->assertSame(DataContainerRecord::class, $resource->getClass());
        $this->assertSame($expectedShortName, $resource->getShortName());
        $this->assertSame(DataContainerStateProvider::class, $resource->getProvider());
        $this->assertSame(DataContainerStateProcessor::class, $resource->getProcessor());
        $this->assertSame($expectedRoutePrefix, $resource->getRoutePrefix());
        $this->assertSame(['_scope' => 'backend'], $resource->getDefaults());
        $this->assertSame("is_granted('ROLE_USER')", $resource->getSecurity());
        $this->assertSame($expectedTable, $resource->getExtraProperties()['contao']['table']);
        $this->assertSame(DataContainerOpenApiFactory::getSchemaPath($expectedTable), $resource->getExtraProperties()['contao']['schema_path']);
        $this->assertSame([], $resource->getMcp());

        $operations = $resource->getOperations();
        $this->assertInstanceOf(Operations::class, $operations);
        $this->assertCount($deletable ? 6 : 5, $operations);

        $operations = iterator_to_array($operations);

        foreach ($operations as $name => $operation) {
            $this->assertSame($name, $operation->getName());
            $this->assertSame($expectedTable, $operation->getExtraProperties()['contao']['table']);
            $this->assertSame(DataContainerStateProvider::class, $operation->getProvider());
            $this->assertSame(DataContainerStateProcessor::class, $operation->getProcessor());
        }

        $this->assertOperation($operations['contao_api_'.$expectedTable.'_get_collection'], GetCollection::class, $expectedShortName, $expectedRoutePrefix);
        $this->assertOperation($operations['contao_api_'.$expectedTable.'_get'], Get::class, $expectedShortName, $expectedRoutePrefix.'/{id}');
        $this->assertOperation($operations['contao_api_'.$expectedTable.'_post'], Post::class, $expectedShortName, $expectedRoutePrefix);
        $this->assertOperation($operations['contao_api_'.$expectedTable.'_patch'], Patch::class, $expectedShortName, $expectedRoutePrefix.'/{id}');
        $this->assertOperation($operations['contao_api_'.$expectedTable.'_move'], Post::class, $expectedShortName, $expectedRoutePrefix.'/{id}/move');
        $this->assertSame(DataContainerMove::class, $operations['contao_api_'.$expectedTable.'_move']->getInput());
        $this->assertFalse($operations['contao_api_'.$expectedTable.'_move']->canRead());

        if ($deletable) {
            $this->assertOperation($operations['contao_api_'.$expectedTable.'_delete'], Delete::class, $expectedShortName, $expectedRoutePrefix.'/{id}');
        } else {
            $this->assertArrayNotHasKey('contao_api_'.$expectedTable.'_delete', $operations);
        }
    }

    private function assertOperation(object $operation, string $expectedClass, string $expectedShortName, string $expectedUriTemplate): void
    {
        $this->assertInstanceOf($expectedClass, $operation);
        $this->assertSame(DataContainerRecord::class, $operation->getClass());
        $this->assertSame($expectedShortName, $operation->getShortName());
        $this->assertSame($expectedUriTemplate, $operation->getUriTemplate());
        $this->assertSame(['_scope' => 'backend'], $operation->getDefaults());
        $this->assertSame("is_granted('ROLE_USER')", $operation->getSecurity());
        $this->assertNull($operation->getOpenapi());
    }

    /**
     * @param list<non-empty-string> $tables
     */
    private function createResourceFinder(array $tables): ResourceFinderInterface
    {
        return new class($tables) implements ResourceFinderInterface {
            /**
             * @param list<non-empty-string> $tables
             */
            public function __construct(private readonly array $tables)
            {
            }

            public function find(): Finder
            {
                return $this->createFinder();
            }

            public function findIn(string $subpath): Finder
            {
                return $this->createFinder();
            }

            private function createFinder(): Finder
            {
                return new class($this->tables) extends Finder {
                    public function __construct(private readonly array $tables)
                    {
                    }

                    public function getIterator(): \Iterator
                    {
                        foreach ($this->tables as $table) {
                            yield $table.'.php' => new SplFileInfo($table.'.php', '', $table.'.php');
                        }
                    }
                };
            }
        };
    }
}
