<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\OpenApi;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Link;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Schema;
use ApiPlatform\OpenApi\OpenApi;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;

final class DataContainerOpenApiFactory implements OpenApiFactoryInterface
{
    public const SCHEMA_PATH_PREFIX = 'dc/';

    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly DataContainerSchemaFactory $schemaFactory,
        private readonly string $apiPrefix,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = clone $openApi->getPaths();
        $schemas = clone ($openApi->getComponents()->getSchemas() ?? new \ArrayObject());

        foreach ($this->resourceMetadataCollectionFactory->create(DataContainerRecord::class) as $resource) {
            $this->addResource($resource, $paths, $schemas);
        }

        return $openApi
            ->withComponents($openApi->getComponents()->withSchemas($schemas))
            ->withPaths($paths)
        ;
    }

    public static function getSchemaPath(string $table): string
    {
        return self::SCHEMA_PATH_PREFIX.$table;
    }

    public function getPathForDataContainerResource(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $apiPrefix = '/'.trim($this->apiPrefix, '/');

        if (str_starts_with($path, $apiPrefix.'/') || $path === $apiPrefix) {
            return $path;
        }

        return $apiPrefix.$path;
    }

    /**
     * @param \ArrayObject<string, Schema> $schemas
     */
    private function addResource(ApiResource $resource, Paths $paths, \ArrayObject $schemas): void
    {
        $contao = $resource->getExtraProperties()['contao'] ?? [];
        $table = $contao['table'] ?? null;
        $schemaPath = $contao['schema_path'] ?? null;
        $shortName = $resource->getShortName();

        if (!\is_string($table) || '' === $table || !\is_string($schemaPath) || '' === $schemaPath || !\is_string($shortName) || '' === $shortName) {
            return;
        }

        $schemaName = str_replace('/', '_', $schemaPath);
        $schemaRef = '#/components/schemas/'.$schemaName;

        foreach ($this->schemaFactory->createOperationSchemas($table) as $action => $schema) {
            $schemas[$schemaName.('read' === $action ? '' : '_'.$action)] = $this->createComponentSchema($schema);
        }

        foreach ($resource->getOperations() ?? [] as $metadata) {
            $operation = $this->createOperation($metadata, $resource, $schemaRef);

            if (!$operation) {
                continue;
            }

            $path = $this->getPathForDataContainerResource($metadata->getUriTemplate());
            $pathItem = $paths->getPath($path) ?? new PathItem();
            $method = 'with'.ucfirst(strtolower($metadata->getMethod()));
            $paths->addPath($path, $pathItem->$method($operation));
        }
    }

    private function createOperation(HttpOperation $metadata, ApiResource $resource, string $schemaRef): Operation|null
    {
        $table = $resource->getExtraProperties()['contao']['table'];
        $shortName = $resource->getShortName();

        $operation = match (true) {
            'move' === ($metadata->getExtraProperties()['contao']['action'] ?? null) => $this->createMoveOperation($table, $shortName, $schemaRef),
            $metadata instanceof GetCollection => $this->createGetCollectionOperation($table, $shortName, $schemaRef),
            $metadata instanceof Get => $this->createGetOperation($table, $shortName, $schemaRef),
            $metadata instanceof Post => $this->createPostOperation($table, $shortName, $schemaRef),
            $metadata instanceof Patch => $this->createPatchOperation($table, $shortName, $schemaRef),
            $metadata instanceof Delete => $this->createDeleteOperation($table, $shortName),
            default => null,
        };

        if (!$operation) {
            return null;
        }

        $operation = $operation->withOperationId($metadata->getName() ?? $operation->getOperationId());

        if (str_contains((string) $metadata->getUriTemplate(), '{id}')) {
            $operation = $operation->withParameters([new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer'])]);
        }

        if (!$metadata instanceof GetCollection && !$metadata instanceof Delete) {
            foreach ($resource->getOperations() ?? [] as $candidate) {
                if ('move' === ($candidate->getExtraProperties()['contao']['action'] ?? null)) {
                    return $this->withMoveLink($operation, $candidate->getName() ?? $shortName.'move');
                }
            }
        }

        return $operation;
    }

    private function withMoveLink(Operation $operation, string $operationId): Operation
    {
        foreach ($operation->getResponses() as $status => $response) {
            $links = clone ($response->getLinks() ?? new \ArrayObject());

            $links['move'] = new Link(
                operationId: $operationId,
                parameters: new \ArrayObject(['id' => '$response.body#/id']),
                description: 'Change the parent or position of this record using the move operation.',
            );

            $operation = $operation->withResponse($status, $response->withLinks($links));
        }

        return $operation;
    }

    private function createGetCollectionOperation(string $tag, string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'getCollection')
            ->withSummary('Collection of '.$shortName.' records')
            ->withParameters([
                new Parameter(name: 'parent', in: 'query', description: 'Parent record ID for a child-table listing.', schema: ['type' => 'integer', 'minimum' => 0]),
                new Parameter(name: 'ptable', in: 'query', description: 'Parent table for a dynamic parent.', schema: ['type' => 'string']),
            ])
            ->withTags([$tag])
            ->withExtensionProperty(OpenApiFactory::API_PLATFORM_TAG, [$tag])
            ->withResponse(200, new Response(
                description: 'A collection of '.$shortName.' records.',
                content: new \ArrayObject([
                    'application/json' => new MediaType($this->createCollectionSchema($schemaRef)),
                ]),
            ))
        ;
    }

    private function createGetOperation(string $tag, string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'get')
            ->withSummary('Fetch a '.$shortName.' record')
            ->withTags([$tag])
            ->withExtensionProperty(OpenApiFactory::API_PLATFORM_TAG, [$tag])
            ->withResponse(200, new Response(
                description: 'A '.$shortName.' record.',
                content: new \ArrayObject([
                    'application/json' => new MediaType($this->createObjectSchema($schemaRef)),
                ]),
            ))
        ;
    }

    private function createPostOperation(string $tag, string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'post')
            ->withSummary('Create a '.$shortName.' record')
            ->withTags([$tag])
            ->withExtensionProperty(OpenApiFactory::API_PLATFORM_TAG, [$tag])
            ->withResponse(201, new Response(
                description: 'The created '.$shortName.' record.',
                content: new \ArrayObject([
                    'application/json' => new MediaType($this->createObjectSchema($schemaRef)),
                ]),
            ))
            ->withRequestBody(new RequestBody(
                description: 'The '.$shortName.' payload.',
                content: new \ArrayObject([
                    'application/json' => new MediaType($this->createObjectSchema($schemaRef.'_create')),
                ]),
                required: true,
            ))
        ;
    }

    private function createPatchOperation(string $tag, string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'patch')
            ->withSummary('Update a '.$shortName.' record')
            ->withTags([$tag])
            ->withExtensionProperty(OpenApiFactory::API_PLATFORM_TAG, [$tag])
            ->withResponse(200, new Response(
                description: 'The updated '.$shortName.' record.',
                content: new \ArrayObject([
                    'application/json' => new MediaType($this->createObjectSchema($schemaRef)),
                ]),
            ))
            ->withRequestBody(new RequestBody(
                description: 'The '.$shortName.' payload.',
                content: new \ArrayObject([
                    'application/merge-patch+json' => new MediaType($this->createObjectSchema($schemaRef.'_update')),
                ]),
                required: true,
            ))
        ;
    }

    private function createMoveOperation(string $tag, string $shortName, string $schemaRef): Operation
    {
        return $this->createPostOperation($tag, $shortName, $schemaRef)
            ->withOperationId($shortName.'move')
            ->withSummary('Move or reorder a '.$shortName.' record')
            ->withParameters([new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer'])])
            ->withResponses(['200' => new Response(
                description: 'The moved record.',
                content: new \ArrayObject(['application/json' => new MediaType($this->createObjectSchema($schemaRef))]),
            )])
            ->withRequestBody(new RequestBody(
                content: new \ArrayObject(['application/json' => new MediaType($this->createObjectSchema($schemaRef.'_move'))]),
                required: true,
            ))
        ;
    }

    private function createDeleteOperation(string $tag, string $shortName): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'delete')
            ->withSummary('Delete a '.$shortName.' record')
            ->withTags([$tag])
            ->withExtensionProperty(OpenApiFactory::API_PLATFORM_TAG, [$tag])
            ->withResponse(204, new Response(description: 'No content.'))
        ;
    }

    private function createComponentSchema(array $schema): Schema
    {
        $componentSchema = new Schema();

        foreach ($schema as $key => $value) {
            $componentSchema[$key] = $value;
        }

        return $componentSchema;
    }

    private function createCollectionSchema(string $ref): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'array';
        $schema['items'] = new Schema();
        $schema['items']['$ref'] = $ref;

        return $schema;
    }

    private function createObjectSchema(string $ref): Schema
    {
        $schema = new Schema();
        $schema['$ref'] = $ref;

        return $schema;
    }
}
