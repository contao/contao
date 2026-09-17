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
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\OpenApi\DataContainerOpenApiFactory;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\DataContainerStateProvider;
use Contao\ApiBundle\Dto\DataContainerMove;
use Contao\ApiBundle\Dto\DataContainerRecord;
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

        $apiResources = [];

        foreach ($this->getTables() as $table) {
            $config = $this->loadDcaConfig($table);

            if (!is_a((string) ($config['dataContainer'] ?? ''), DC_Table::class, true)) {
                continue;
            }

            if (($config['closed'] ?? false) === true) {
                continue;
            }

            $apiResources[] = $this->createResource($table, $config);
        }

        return new ResourceMetadataCollection($resourceClass, $apiResources);
    }

    private function createResource(string $table, array $config): ApiResource
    {
        $shortName = $this->getShortName($table);

        return new ApiResource()
            ->withClass(DataContainerRecord::class)
            ->withShortName($shortName)
            ->withProvider(DataContainerStateProvider::class)
            ->withProcessor(DataContainerStateProcessor::class)
            ->withRoutePrefix($this->getRoutePrefix($table))
            ->withDefaults(['_scope' => 'backend'])
            ->withSecurity("is_granted('ROLE_USER')")
            ->withMcp([])
            ->withExtraProperties($this->getExtraProperties($table))
            ->withOperations($this->createOperations($table, $shortName, $config))
        ;
    }

    /**
     * @return Operations<HttpOperation>
     */
    private function createOperations(string $table, string $shortName, array $config): Operations
    {
        $operations = [
            'get_collection' => new GetCollection(),
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

        $configured = [];

        foreach ($operations as $action => $operation) {
            $name = 'contao_api_'.$table.'_'.$action;
            $item = 'move' === $action || (!$operation instanceof GetCollection && !$operation instanceof Post);

            $configured[$name] = $operation
                ->withName($name)
                ->withClass(DataContainerRecord::class)
                ->withShortName($shortName)
                ->withUriTemplate($this->getRoutePrefix($table).($item ? '/{id}' : '').('move' === $action ? '/move' : ''))
                ->withProvider(DataContainerStateProvider::class)
                ->withProcessor(DataContainerStateProcessor::class)
                ->withDefaults(['_scope' => 'backend'])
                ->withSecurity("is_granted('ROLE_USER')")
                ->withExtraProperties(['contao' => $this->getExtraProperties($table)['contao'] + ['action' => $action]])
            ;
        }

        return new Operations($configured);
    }

    private function getExtraProperties(string $table): array
    {
        return [
            'contao' => [
                'table' => $table,
                'schema_path' => DataContainerOpenApiFactory::getSchemaPath($table),
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
        $shortName = preg_replace('/^tl_/', '', $table) ?? $table;

        return str_replace(' ', '', ucwords(str_replace('_', ' ', $shortName)));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDcaConfig(string $table): array
    {
        $this->framework->getAdapter(Controller::class)->loadDataContainer($table);

        return $GLOBALS['TL_DCA'][$table]['config'] ?? [];
    }

    private function getRoutePrefix(string $table): string
    {
        return '/'.trim($this->dataContainerApiPrefix, '/').'/'.$table;
    }
}
