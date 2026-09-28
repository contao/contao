<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\OpenApi\DataContainerOpenApiFactory;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProvider;
use Contao\ApiBundle\DataContainer\DataContainerPage;
use Contao\ApiBundle\Dto\DataContainerMove;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DC_Table;

final class DataContainerResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly ContaoFramework $framework,
        private readonly ResourceFinderInterface $resourceFinder,
        private readonly string $dataContainerApiPrefix,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (DataContainerRecord::class !== $resourceClass) {
            return $this->decorated->create($resourceClass);
        }

        $this->framework->initialize();

        $configs = [];

        foreach ($this->getTables() as $table) {
            $config = $this->loadDcaConfig($table);

            if (!is_a((string) ($config['dataContainer'] ?? ''), DC_Table::class, true)) {
                continue;
            }

            $configs[$table] = $config;
        }

        $apiResources = array_map(fn (array $path): ApiResource => $this->createResource($path, $configs[$path[array_key_last($path)]]), $this->getResourcePaths($configs));

        return new ResourceMetadataCollection($resourceClass, $apiResources);
    }

    /**
     * @param non-empty-list<string> $path
     */
    private function createResource(array $path, array $config): ApiResource
    {
        $table = $path[array_key_last($path)];
        $shortName = $this->getShortName($table);

        return new ApiResource()
            ->withClass(DataContainerRecord::class)
            ->withShortName($shortName)
            ->withProvider(DataContainerStateProvider::class)
            ->withProcessor(DataContainerStateProcessor::class)
            ->withRoutePrefix($this->getRoutePrefix($path))
            ->withDefaults(['_scope' => 'backend'])
            ->withStateless(true)
            ->withSecurity("is_granted('ROLE_USER')")
            ->withMcp([])
            ->withExtraProperties($this->getExtraProperties($path))
            ->withOperations($this->createOperations($path, $config))
        ;
    }

    private function createCollectionOperation(): GetCollection
    {
        $maximum = (int) $this->framework->getAdapter(Config::class)->get('maxResultsPerPage');

        // Enforce a finite API page size to reduce the risk of resource exhaustion, even
        // when the backend has no configured maximum
        if ($maximum < 1) {
            $maximum = DataContainerPage::DEFAULT_ITEMS_PER_PAGE;
        }

        return new GetCollection(
            paginationEnabled: true,
            paginationPartial: true, // Omit the total count and last page to avoid a separate count query
            paginationClientEnabled: false, // Clients cannot disable pagination
            paginationClientItemsPerPage: true, // Clients can choose the page size within the configured maximum
            paginationItemsPerPage: min(DataContainerPage::DEFAULT_ITEMS_PER_PAGE, $maximum),
            paginationMaximumItemsPerPage: $maximum,
        );
    }

    /**
     * @param non-empty-list<string> $path
     *
     * @return Operations<HttpOperation>
     */
    private function createOperations(array $path, array $config): Operations
    {
        $operations = [
            'get_collection' => $this->createCollectionOperation(),
            'get' => new Get(),
            'post' => new Post(),
            'patch' => new Patch(),
        ];

        if (!($config['notDeletable'] ?? false)) {
            $operations['delete'] = new Delete();
        }

        if (!($config['notSortable'] ?? false) && !($config['notEditable'] ?? false)) {
            $operations['move'] = new Post(input: DataContainerMove::class, read: false, status: 200, denormalizationContext: ['allow_extra_attributes' => false]);
        }

        return new Operations($this->configureOperations($operations, $path, $config));
    }

    /**
     * @param array<string, HttpOperation> $operations
     * @param non-empty-list<string>       $path
     * @param array<string, mixed>         $config
     *
     * @return array<string, HttpOperation>
     */
    private function configureOperations(array $operations, array $path, array $config): array
    {
        $configured = [];

        foreach ([false, true] as $recursive) {
            $table = $path[array_key_last($path)];

            if ($recursive && !\in_array($table, (array) ($config['ctable'] ?? []), true)) {
                continue;
            }

            foreach ($operations as $action => $operation) {
                $item = 'move' === $action || (!$operation instanceof GetCollection && !$operation instanceof Post);
                $suffix = ($recursive ? '/{nested}/'.$this->getResourceName($table) : '').($item ? '/{id}' : '').('move' === $action ? '/move' : '');
                $name = 'contao_api_dc_'.implode('_', array_map($this->getResourceName(...), $path)).($recursive ? '_nested' : '').'_'.$action;
                $extra = $this->getExtraProperties($path)['contao'] + ['action' => $action];

                if ($recursive) {
                    $extra['recursive_parent'] = ['table' => $table, 'parameter' => 'nested', 'segment' => $this->getResourceName($table)];
                }

                $configured[$name] = $operation
                    ->withName($name)
                    ->withClass(DataContainerRecord::class)
                    ->withShortName($this->getShortName($table))
                    ->withUriTemplate($this->getRoutePrefix($path).$suffix)
                    ->withUriVariables($this->getUriVariables($path, $item, $recursive))
                    ->withRequirements($recursive ? ['nested' => '.+'] : [])
                    ->withProvider(DataContainerStateProvider::class)
                    ->withProcessor(DataContainerStateProcessor::class)
                    ->withDefaults(['_scope' => 'backend'])
                    ->withStateless(true)
                    ->withSecurity("is_granted('ROLE_USER')")
                    ->withExtraProperties(['contao' => $extra])
                ;
            }
        }

        return $configured;
    }

    /**
     * @param non-empty-list<string> $path
     */
    private function getExtraProperties(array $path): array
    {
        $table = $path[array_key_last($path)];

        return [
            'contao' => [
                'table' => $table,
                'resource' => implode('/', array_map($this->getResourceName(...), $path)),
                'category' => $this->getShortName($path[0]),
                'parents' => array_map(fn (string $parent): array => ['table' => $parent, 'parameter' => $this->getParameterName($parent)], \array_slice($path, 0, -1)),
                'schema_path' => DataContainerOpenApiFactory::getSchemaPath($this->getResourceName($table)),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function getTables(): array
    {
        $tables = [];

        foreach ($this->resourceFinder->findIn('dca')->files()->name('*.php') as $file) {
            $tables[] = $file->getBasename('.php');
        }

        sort($tables);

        return $tables;
    }

    private function getShortName(string $table): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $this->getResourceName($table))));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDcaConfig(string $table): array
    {
        $this->framework->getAdapter(Controller::class)->loadDataContainer($table);

        return $GLOBALS['TL_DCA'][$table]['config'] ?? [];
    }

    /**
     * @param non-empty-list<string> $path
     */
    private function getRoutePrefix(array $path): string
    {
        $route = '/'.trim($this->dataContainerApiPrefix, '/');

        foreach ($path as $index => $table) {
            $route .= '/'.$this->getResourceName($table);

            if ($index < \count($path) - 1) {
                $route .= '/{'.$this->getParameterName($table).'}';
            }
        }

        return $route;
    }

    /**
     * @param array<string, array<string, mixed>> $configs
     *
     * @return list<non-empty-list<string>>
     */
    private function getResourcePaths(array $configs): array
    {
        $children = [];
        $hasParent = [];

        foreach ($configs as $table => $config) {
            $parent = $config['ptable'] ?? null;

            if (\is_string($parent) && $parent !== $table && isset($configs[$parent])) {
                $children[$parent][] = $table;
                $hasParent[$table] = true;
            }

            foreach ((array) ($config['ctable'] ?? []) as $child) {
                if ($child === $table || !isset($configs[$child])) {
                    continue;
                }

                if (!\in_array($child, $children[$table] ?? [], true)) {
                    $children[$table][] = $child;
                }

                $hasParent[$child] = true;
            }
        }

        $paths = [];

        foreach (array_keys($configs) as $table) {
            if (!isset($hasParent[$table]) && !($configs[$table]['closed'] ?? false)) {
                $this->appendResourcePaths([$table], $configs, $children, $paths);
            }
        }

        return $paths;
    }

    /**
     * @param non-empty-list<string>              $path
     * @param array<string, array<string, mixed>> $configs
     * @param array<string, list<string>>         $children
     * @param list<non-empty-list<string>>        $paths
     */
    private function appendResourcePaths(array $path, array $configs, array $children, array &$paths): void
    {
        $table = $path[array_key_last($path)];
        $paths[] = $path;

        foreach ($children[$table] ?? [] as $child) {
            if (!\in_array($child, $path, true) && !($configs[$child]['closed'] ?? false)) {
                $this->appendResourcePaths([...$path, $child], $configs, $children, $paths);
            }
        }
    }

    private function getResourceName(string $table): string
    {
        return str_starts_with($table, 'tl_') ? substr($table, 3) : $table;
    }

    private function getParameterName(string $table): string
    {
        return $this->getResourceName($table).'_id';
    }

    /**
     * @param non-empty-list<string> $path
     *
     * @return array<string, Link>
     */
    private function getUriVariables(array $path, bool $item, bool $recursive = false): array
    {
        $variables = [];

        foreach (\array_slice($path, 0, -1) as $parent) {
            $parameter = $this->getParameterName($parent);
            $variables[$parameter] = new Link(parameterName: $parameter, fromClass: DataContainerRecord::class, identifiers: ['id']);
        }

        if ($recursive) {
            $variables['nested'] = new Link(parameterName: 'nested', fromClass: DataContainerRecord::class, identifiers: ['id']);
        }

        if ($item) {
            $variables['id'] = new Link(parameterName: 'id', fromClass: DataContainerRecord::class, identifiers: ['id']);
        }

        return $variables;
    }
}
