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

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
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
        $paths = new Paths();

        $schemas = $openApi->getComponents()->getSchemas() ?? new \ArrayObject();

        foreach ($this->resourceMetadataCollectionFactory->create(DataContainerRecord::class) as $resource) {
            $contao = $resource->getExtraProperties()['contao'] ?? null;
            $shortName = $resource->getShortName();

            if (!\is_array($contao) || !\is_string($shortName) || '' === $shortName) {
                continue;
            }

            $table = $contao['table'] ?? null;
            $schemaPath = $contao['schema_path'] ?? null;

            if (!\is_string($table) || '' === $table || !\is_string($schemaPath) || '' === $schemaPath) {
                continue;
            }

            $schema = $this->schemaFactory->create($table);
            $schemaName = str_replace('/', '_', $schemaPath);
            $schemaRef = '#/components/schemas/'.$schemaName;

            if (!isset($schemas[$schemaName])) {
                $schemas[$schemaName] = $this->createComponentSchema($schema);
            }

            foreach ($resource->getOperations() as $operation) {
                if ($operation instanceof GetCollection) {
                    $path = $this->getPathForDataContainerResource($operation->getUriTemplate());
                    $pathItem = $paths->getPath($path) ?? new PathItem();
                    $paths->addPath($path, $pathItem->withGet($this->createGetCollectionOperation($table, $shortName, $schemaRef)));
                    continue;
                }

                if ($operation instanceof Get) {
                    $path = $this->getPathForDataContainerResource($operation->getUriTemplate());
                    $pathItem = $paths->getPath($path) ?? new PathItem();
                    $paths->addPath($path, $pathItem->withGet($this->createGetOperation($table, $shortName, $schemaRef)));
                    continue;
                }

                if ($operation instanceof Post) {
                    $path = $this->getPathForDataContainerResource($operation->getUriTemplate());
                    $pathItem = $paths->getPath($path) ?? new PathItem();
                    $paths->addPath($path, $pathItem->withPost($this->createPostOperation($table, $shortName, $schemaRef)));
                    continue;
                }

                if ($operation instanceof Patch) {
                    $path = $this->getPathForDataContainerResource($operation->getUriTemplate());
                    $pathItem = $paths->getPath($path) ?? new PathItem();
                    $paths->addPath($path, $pathItem->withPatch($this->createPatchOperation($table, $shortName, $schemaRef)));
                    continue;
                }

                if ($operation instanceof Delete) {
                    $path = $this->getPathForDataContainerResource($operation->getUriTemplate());
                    $pathItem = $paths->getPath($path) ?? new PathItem();
                    $paths->addPath($path, $pathItem->withDelete($this->createDeleteOperation($table, $shortName)));
                }
            }
        }

        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            $paths->addPath($path, $pathItem);
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

    private function createGetCollectionOperation(string $tag, string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'getCollection')
            ->withSummary('Collection of '.$shortName.' records')
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
                    'application/json' => new MediaType($this->createObjectSchema($schemaRef)),
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
                    'application/json' => new MediaType($this->createObjectSchema($schemaRef)),
                ]),
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
