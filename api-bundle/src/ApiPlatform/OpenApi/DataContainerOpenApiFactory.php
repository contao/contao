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
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\Pagination\Pagination;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\DataContainer;
use Contao\StringUtil;

final class DataContainerOpenApiFactory implements OpenApiFactoryInterface
{
    public const SCHEMA_PATH_PREFIX = 'dc/';

    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly DataContainerSchemaFactory $schemaFactory,
        private readonly Pagination $pagination,
        private readonly string $apiPrefix,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = clone $openApi->getPaths();
        $schemas = clone ($openApi->getComponents()->getSchemas() ?? new \ArrayObject());

        $resources = iterator_to_array($this->resourceMetadataCollectionFactory->create(DataContainerRecord::class));

        foreach ($resources as $resource) {
            $this->addResource($resource, $paths, $schemas);
        }

        return $openApi
            ->withComponents($openApi->getComponents()->withSchemas($schemas))
            ->withPaths($paths)
            ->withTags($this->getTags($openApi, $resources))
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
        $category = $resource->getExtraProperties()['contao']['category'] ?? $shortName;

        if (!\is_string($category) || '' === $category) {
            $category = $shortName;
        }

        $operation = match (true) {
            'move' === ($metadata->getExtraProperties()['contao']['action'] ?? null) => $this->createMoveOperation($shortName, $schemaRef),
            $metadata instanceof GetCollection => $this->withPaginationParameters($this->createGetCollectionOperation($table, $shortName, $schemaRef), $metadata),
            $metadata instanceof Get => $this->createGetOperation($shortName, $schemaRef),
            $metadata instanceof Post => $this->createPostOperation($shortName, $schemaRef),
            $metadata instanceof Patch => $this->createPatchOperation($shortName, $schemaRef),
            $metadata instanceof Delete => $this->createDeleteOperation($shortName),
            default => null,
        };

        if (!$operation) {
            return null;
        }

        $operation = $operation
            ->withOperationId($metadata->getName() ?? $operation->getOperationId())
            ->withTags([$category])
            ->withExtensionProperty(OpenApiFactory::API_PLATFORM_TAG, [$category])
        ;

        $operation = $operation->withParameters([...$this->getPathParameters($metadata), ...($operation->getParameters() ?? [])]);

        if (!$metadata instanceof GetCollection && !$metadata instanceof Delete) {
            foreach ($resource->getOperations() ?? [] as $candidate) {
                $candidateContao = $candidate->getExtraProperties()['contao'] ?? [];
                $recursive = isset($metadata->getExtraProperties()['contao']['recursive_parent']);

                if ('move' === ($candidateContao['action'] ?? null) && $recursive === isset($candidateContao['recursive_parent'])) {
                    return $this->withMoveLink($operation, $candidate->getName() ?? $shortName.'move', $metadata);
                }
            }
        }

        return $operation;
    }

    private function withPaginationParameters(Operation $operation, GetCollection $metadata): Operation
    {
        $options = $this->pagination->getOptions();
        $schema = ['type' => 'integer', 'minimum' => 1, 'default' => $this->pagination->getLimit($metadata)];
        $maximum = $metadata->getPaginationMaximumItemsPerPage() ?? $options['maximum_items_per_page'];

        if (null !== $maximum) {
            $schema['maximum'] = $maximum;
        }

        return $operation->withParameters([
            ...$operation->getParameters(),
            new Parameter(name: $options['page_parameter_name'], in: 'query', description: 'Collection page.', schema: ['type' => 'integer', 'minimum' => 1, 'default' => 1]),
            new Parameter(name: $options['items_per_page_parameter_name'], in: 'query', description: 'Records per page, capped at the configured maximum.', schema: $schema),
        ]);
    }

    private function withMoveLink(Operation $operation, string $operationId, HttpOperation $metadata): Operation
    {
        $linkParameters = ['id' => '$response.body#/id'];

        foreach ($metadata->getExtraProperties()['contao']['parents'] ?? [] as $parent) {
            if (\is_string($parent['parameter'] ?? null)) {
                $linkParameters[$parent['parameter']] = '$request.path.'.$parent['parameter'];
            }
        }

        if (\is_string($metadata->getExtraProperties()['contao']['recursive_parent']['parameter'] ?? null)) {
            $parameter = $metadata->getExtraProperties()['contao']['recursive_parent']['parameter'];
            $linkParameters[$parameter] = '$request.path.'.$parameter;
        }

        foreach ($operation->getResponses() as $status => $response) {
            $links = clone ($response->getLinks() ?? new \ArrayObject());

            $links['move'] = new Link(
                operationId: $operationId,
                parameters: new \ArrayObject($linkParameters),
                description: 'Change the parent or position of this record using the move operation.',
            );

            $operation = $operation->withResponse($status, $response->withLinks($links));
        }

        return $operation;
    }

    private function createGetCollectionOperation(string $table, string $shortName, string $schemaRef): Operation
    {
        $parameters = [];

        if ($this->supportsSorting($table)) {
            array_unshift($parameters, new Parameter(name: 'sort', in: 'query', description: 'Ordered backend sorting choices. Currently one choice is supported, e.g. title or title ASC/title DESC when the field allows both directions. Omit to use the configured backend default order.', schema: ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 1], style: 'form', explode: false));
        }

        return new Operation()
            ->withOperationId($shortName.'getCollection')
            ->withSummary('Collection of '.$shortName.' records')
            ->withParameters($parameters)
            ->withResponse(200, new Response(
                description: 'A collection of '.$shortName.' records.',
                content: new \ArrayObject([
                    'application/json' => new MediaType($this->createCollectionSchema($schemaRef)),
                ]),
            ))
        ;
    }

    private function supportsSorting(string $table): bool
    {
        $sorting = $GLOBALS['TL_DCA'][$table]['list']['sorting'] ?? [];

        if (
            !\in_array($sorting['mode'] ?? null, [DataContainer::MODE_SORTABLE, DataContainer::MODE_PARENT], true)
            || !\in_array('sort', StringUtil::trimsplit('[;,]', $sorting['panelLayout'] ?? ''), true)
        ) {
            return false;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['fields'] ?? [] as $field) {
            if ($field['sorting'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Parameter>
     */
    private function getPathParameters(HttpOperation $metadata): array
    {
        $parameters = [];

        foreach ($metadata->getExtraProperties()['contao']['parents'] ?? [] as $parent) {
            $name = $parent['parameter'] ?? null;

            if (\is_string($name)) {
                $parameters[] = new Parameter(name: $name, in: 'path', required: true, schema: ['type' => 'integer', 'minimum' => 1]);
            }
        }

        $recursive = $metadata->getExtraProperties()['contao']['recursive_parent'] ?? null;

        if (\is_array($recursive) && \is_string($recursive['parameter'] ?? null) && \is_string($recursive['segment'] ?? null)) {
            $parameters[] = new Parameter(
                name: $recursive['parameter'],
                in: 'path',
                description: 'Nested parent chain alternating record IDs and resource segments, for example "4/'.$recursive['segment'].'/5".',
                required: true,
                schema: ['type' => 'string', 'pattern' => '^\\d+(?:/'.$recursive['segment'].'/\\d+)*$'],
                example: '4/'.$recursive['segment'].'/5',
            );
        }

        if (str_contains((string) $metadata->getUriTemplate(), '{id}')) {
            $parameters[] = new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer', 'minimum' => 1]);
        }

        return $parameters;
    }

    private function createGetOperation(string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'get')
            ->withSummary('Fetch a '.$shortName.' record')
            ->withResponse(200, new Response(
                description: 'A '.$shortName.' record.',
                content: new \ArrayObject([
                    'application/json' => new MediaType($this->createObjectSchema($schemaRef)),
                ]),
            ))
        ;
    }

    private function createPostOperation(string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'post')
            ->withSummary('Create a '.$shortName.' record')
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

    private function createPatchOperation(string $shortName, string $schemaRef): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'patch')
            ->withSummary('Update a '.$shortName.' record')
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

    private function createMoveOperation(string $shortName, string $schemaRef): Operation
    {
        return $this->createPostOperation($shortName, $schemaRef)
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

    private function createDeleteOperation(string $shortName): Operation
    {
        return new Operation()
            ->withOperationId($shortName.'delete')
            ->withSummary('Delete a '.$shortName.' record')
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

    /**
     * Groups nested data container resources under their root resource category while
     * preserving unrelated OpenAPI tags.
     *
     * @param list<ApiResource> $resources
     *
     * @return list<Tag>
     */
    private function getTags(OpenApi $openApi, array $resources): array
    {
        $resourceNames = array_filter(array_map(static fn (ApiResource $resource): string|null => $resource->getShortName(), $resources));

        $categories = array_values(array_unique(array_filter(
            array_map(static fn (ApiResource $resource): mixed => $resource->getExtraProperties()['contao']['category'] ?? $resource->getShortName(), $resources),
            static fn (mixed $category): bool => \is_string($category) && '' !== $category,
        )));

        $tags = [];

        foreach ($openApi->getTags() as $tag) {
            if (!\in_array($tag->getName(), $resourceNames, true) || \in_array($tag->getName(), $categories, true)) {
                $tags[$tag->getName()] = $tag;
            }
        }

        foreach ($categories as $category) {
            $tags[$category] ??= new Tag(name: $category, description: "Resource '$category' operations.");
        }

        return array_values($tags);
    }
}
