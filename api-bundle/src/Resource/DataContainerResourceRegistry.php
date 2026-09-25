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
        $schemas = $this->schemaFactory->createOperationSchemas($name);

        return [
            'resource' => $name,
            'title' => $resource->getShortName(),
            'operations' => array_keys($operations),
            'schema' => $schemas['read'],
            'operationSchemas' => array_intersect_key($schemas, $operations, array_flip(['create', 'update', 'move'])),
        ];
    }

    public function getOperation(string $name, string $action): HttpOperation
    {
        return $this->getOperations($this->getResource($name))[$action]
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
            $table = $resource->getExtraProperties()['contao']['table'] ?? null;

            if (\is_string($table) && '' !== $table) {
                $resources[$table] = $resource;
            }
        }

        return $resources;
    }

    /**
     * @return array<string, HttpOperation>
     */
    private function getOperations(ApiResource $resource): array
    {
        $operations = [];

        foreach ($resource->getOperations() ?? [] as $operation) {
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
}
