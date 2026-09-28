<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;

final class DataContainerResourceRegistry
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory,
        private readonly DataContainerSchemaFactory $schemaFactory,
    ) {
    }

    public function discover(string $query = ''): array
    {
        $resources = [];

        foreach ($this->getResources() as $name => $resource) {
            if ('' !== $query && !str_contains(strtolower($name.' '.$resource->getShortName()), strtolower($query))) {
                continue;
            }

            $resources[] = ['resource' => $name, 'title' => $resource->getShortName()];
        }

        return ['resources' => $resources];
    }

    public function describe(string $name): array
    {
        $resource = $this->getResource($name);
        $operations = $this->getOperations($resource);
        $table = $this->getTable($resource) ?? throw new \LogicException('The data container resource has no table metadata.');
        $contao = $resource->getExtraProperties()['contao'] ?? [];
        $schemas = $this->schemaFactory->createOperationSchemas($table);

        return [
            'resource' => $name,
            'title' => $resource->getShortName(),
            'parentParameters' => array_column($contao['parents'] ?? [], 'parameter'),
            'recursiveParentParameter' => $this->hasRecursiveOperations($resource) ? 'nested' : null,
            'operations' => array_keys($operations),
            'schema' => $schemas['read'],
            'operationSchemas' => array_intersect_key($schemas, $operations, array_flip(['create', 'update', 'move'])),
        ];
    }

    public function getOperation(string $name, string $action, bool $recursive = false): HttpOperation
    {
        return $this->getOperations($this->getResource($name), $recursive)[$action]
            ?? throw new OperationNotFoundException(\sprintf('Resource "%s" does not support "%s".', $name, $action));
    }

    private function getResource(string $name): ApiResource
    {
        return $this->getResources()[$name]
            ?? throw new \OutOfBoundsException(\sprintf('Unknown resource "%s".', $name));
    }

    /**
     * @return array<string, ApiResource>
     */
    private function getResources(): array
    {
        $resources = [];

        foreach ($this->metadataFactory->create(DataContainerRecord::class) as $resource) {
            $name = $resource->getExtraProperties()['contao']['resource'] ?? null;

            if (\is_string($name) && '' !== $name) {
                $resources[$name] = $resource;
            }
        }

        return $resources;
    }

    private function getTable(ApiResource $resource): string|null
    {
        $table = $resource->getExtraProperties()['contao']['table'] ?? null;

        return \is_string($table) && '' !== $table ? $table : null;
    }

    /**
     * @return array<string, HttpOperation>
     */
    private function getOperations(ApiResource $resource, bool $recursive = false): array
    {
        $operations = [];

        foreach ($resource->getOperations() ?? [] as $operation) {
            if ($recursive !== isset($operation->getExtraProperties()['contao']['recursive_parent'])) {
                continue;
            }

            $action = match (true) {
                'move' === ($operation->getExtraProperties()['contao']['action'] ?? null) => 'move',
                $operation instanceof GetCollection => 'list',
                $operation instanceof Get => 'read',
                $operation instanceof Post => 'create',
                $operation instanceof Patch => 'update',
                $operation instanceof Delete => 'delete',
                default => null,
            };

            if (null !== $action) {
                $operations[$action] = $operation;
            }
        }

        return $operations;
    }

    private function hasRecursiveOperations(ApiResource $resource): bool
    {
        foreach ($resource->getOperations() ?? [] as $operation) {
            if (isset($operation->getExtraProperties()['contao']['recursive_parent'])) {
                return true;
            }
        }

        return false;
    }
}
