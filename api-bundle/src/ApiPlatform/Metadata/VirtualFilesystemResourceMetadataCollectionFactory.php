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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;

final class VirtualFilesystemResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly SchemaAwareObjectNormalizer $objectNormalizer,
        private readonly UploadSizeProvider $uploadSizeProvider,
        private readonly VirtualFilesystemRegistry $filesystems,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (VirtualFilesystemItem::class !== $resourceClass) {
            return $this->decorated->create($resourceClass);
        }

        $resources = clone $this->decorated->create($resourceClass);

        foreach ($this->filesystems->findByTags(['api_exposure' => true]) as $name => $tags) {
            $resources[] = $this->createResource($name, $tags);
        }

        return $resources;
    }

    private function createResource(string $name, array $tags): ApiResource
    {
        $path = $tags['api_path'] ?? null;

        if (!\is_string($path) || !str_starts_with($path, '/') || str_ends_with($path, '/')) {
            throw new \InvalidArgumentException(\sprintf('The API path for filesystem "%s" must start with "/" and have no trailing "/".', $name));
        }

        $prefix = 'contao_api_'.$name;
        $operations = [
            $prefix.'_get_collection' => $this->createCollectionOperation($path),
            $prefix.'_move' => $this->createMoveOperation($path),
            $prefix.'_get' => $this->createGetOperation($path),
            $prefix.'_upload' => $this->createUploadOperation($path),
            $prefix.'_metadata' => $this->createMetadataOperation($path),
        ];
        $metadata = ['filesystem' => $name, 'relation_filesystem' => $tags['api_relation_filesystem'] ?? $name];

        foreach ($operations as $key => $operation) {
            $operation = $operation->withExtraProperties([
                'contao' => $metadata + ($operation->getExtraProperties()['contao'] ?? []),
            ]);
            $operations[$key] = $this->configureOperation($operation, substr($key, \strlen($prefix) + 1), $tags['api_operation_attributes'] ?? []);
        }

        return new ApiResource(
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            description: 'A file or directory in a virtual filesystem.',
            operations: $operations,
            defaults: ['_scope' => 'backend'],
            mcp: [],
            extraProperties: ['contao' => $metadata],
        );
    }

    private function configureOperation(HttpOperation $operation, string $name, array $attributes): HttpOperation
    {
        $unknown = array_diff(array_keys($attributes), ['*', 'get_collection', 'get', 'upload', 'move', 'metadata']);

        if ($unknown) {
            throw new \InvalidArgumentException(\sprintf('Unknown VFS API operation "%s" in api_operation_attributes.', reset($unknown)));
        }

        foreach (array_replace($attributes['*'] ?? [], $attributes[$name] ?? []) as $attribute => $value) {
            $method = 'with'.ucfirst($attribute);

            if (!method_exists($operation, $method)) {
                throw new \InvalidArgumentException(\sprintf('Unknown VFS API operation attribute "%s" for "%s".', $attribute, $name));
            }

            if ('extraProperties' === $attribute && \is_array($value)) {
                $value = array_replace_recursive($operation->getExtraProperties(), $value);
            }

            $operation = $operation->$method($value);
        }

        return $operation;
    }

    private function createCollectionOperation(string $path): GetCollection
    {
        return new GetCollection(
            uriTemplate: $path,
            itemUriTemplate: $path.'/{pathOrUuid}',
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            paginationEnabled: false,
            defaults: ['_scope' => 'backend'],
            provider: 'contao_api.api_platform.virtual_filesystem_state_provider',
        );
    }

    private function createGetOperation(string $path): Get
    {
        return new Get(
            uriTemplate: $path.'/{pathOrUuid}',
            uriVariables: ['pathOrUuid' => new Link(fromClass: VirtualFilesystemItem::class, identifiers: ['path'], description: 'The file path or UUID.')],
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            requirements: ['pathOrUuid' => '.+'],
            defaults: ['_scope' => 'backend'],
            provider: 'contao_api.api_platform.virtual_filesystem_state_provider',
        );
    }

    private function createUploadOperation(string $path): Put
    {
        $maximumUploadSize = $this->uploadSizeProvider->getMaximumUploadSize();

        return new Put(
            uriTemplate: $path.'/{pathOrUuid}',
            uriVariables: ['pathOrUuid' => new Link(fromClass: VirtualFilesystemItem::class, identifiers: ['path'], description: 'The file path or UUID.')],
            inputFormats: ['binary' => ['application/octet-stream']],
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            requirements: ['pathOrUuid' => '.+'],
            defaults: ['_scope' => 'backend'],
            openapi: new OpenApiOperation(
                summary: 'Upload a file',
                description: 'Uploads raw file contents with PUT. An existing file at the path or UUID is replaced.',
                requestBody: new RequestBody(
                    description: 'The raw contents of the file.',
                    content: new \ArrayObject([
                        'application/octet-stream' => new MediaType(new \ArrayObject(['type' => 'string', 'format' => 'binary', 'maxLength' => $maximumUploadSize])),
                    ]),
                    required: true,
                ),
            ),
            read: false,
            deserialize: false,
            processor: 'contao_api.api_platform.virtual_filesystem_state_processor',
        );
    }

    private function createMoveOperation(string $path): Post
    {
        return new Post(
            uriTemplate: $path.'_operations/move',
            itemUriTemplate: $path.'/{pathOrUuid}',
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            defaults: ['_scope' => 'backend'],
            input: VirtualFilesystemMove::class,
            read: false,
            status: 200,
            processor: 'contao_api.api_platform.virtual_filesystem_state_processor',
        );
    }

    private function createMetadataOperation(string $path): Post
    {
        return new Post(
            uriTemplate: $path.'_operations/metadata',
            inputFormats: ['json' => ['application/json']],
            itemUriTemplate: $path.'/{pathOrUuid}',
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            defaults: ['_scope' => 'backend'],
            input: false,
            openapi: new OpenApiOperation(
                summary: 'Update file metadata',
                description: 'Updates metadata for the file identified by path or UUID in the request body without changing its contents.',
                requestBody: new RequestBody(
                    description: 'The file path or UUID and metadata values to update.',
                    content: new \ArrayObject(['application/json' => new MediaType(new \ArrayObject($this->getMetadataRequestSchema()))]),
                    required: true,
                ),
            ),
            read: false,
            deserialize: false,
            status: 200,
            processor: 'contao_api.api_platform.virtual_filesystem_state_processor',
            extraProperties: ['contao' => ['operation' => 'metadata']],
        );
    }

    private function getMetadataRequestSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => ['type' => 'string', 'minLength' => 1, 'description' => 'The file path or UUID.'],
                'data' => $this->objectNormalizer->getJsonSchema(ExtraMetadata::class),
            ],
            'required' => ['path', 'data'],
            'additionalProperties' => false,
        ];
    }
}
