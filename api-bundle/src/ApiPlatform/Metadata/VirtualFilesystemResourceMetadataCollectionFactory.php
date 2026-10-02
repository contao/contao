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

final class VirtualFilesystemResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly SchemaAwareObjectNormalizer $objectNormalizer,
        private readonly UploadSizeProvider $uploadSizeProvider,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (VirtualFilesystemItem::class !== $resourceClass) {
            return $this->decorated->create($resourceClass);
        }

        $resource = new ApiResource(
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            description: 'A file or directory in the permission-aware virtual filesystem.',
            operations: [
                'contao_api_files_get_collection' => $this->createCollectionOperation(),
                'contao_api_files_move' => $this->createMoveOperation(),
                'contao_api_files_get' => $this->createGetOperation(),
                'contao_api_files_upload' => $this->createUploadOperation(),
                'contao_api_files_metadata' => $this->createMetadataOperation(),
            ],
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
            mcp: [],
        );

        return new ResourceMetadataCollection($resourceClass, [$resource]);
    }

    private function createCollectionOperation(): GetCollection
    {
        return new GetCollection(
            uriTemplate: '/files',
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            paginationEnabled: false,
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
            provider: 'contao_api.api_platform.virtual_filesystem_state_provider',
        );
    }

    private function createGetOperation(): Get
    {
        return new Get(
            uriTemplate: '/files/{pathOrUuid}',
            uriVariables: ['pathOrUuid' => new Link(fromClass: VirtualFilesystemItem::class, identifiers: ['path'], description: 'The file path or UUID.')],
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            requirements: ['pathOrUuid' => '.+'],
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
            provider: 'contao_api.api_platform.virtual_filesystem_state_provider',
        );
    }

    private function createUploadOperation(): Put
    {
        $maximumUploadSize = $this->uploadSizeProvider->getMaximumUploadSize();

        return new Put(
            uriTemplate: '/files/{pathOrUuid}',
            uriVariables: ['pathOrUuid' => new Link(fromClass: VirtualFilesystemItem::class, identifiers: ['path'], description: 'The file path or UUID.')],
            inputFormats: ['binary' => ['application/octet-stream']],
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            requirements: ['pathOrUuid' => '.+'],
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
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

    private function createMoveOperation(): Post
    {
        return new Post(
            uriTemplate: '/files_operations/move',
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
            input: VirtualFilesystemMove::class,
            read: false,
            status: 200,
            processor: 'contao_api.api_platform.virtual_filesystem_state_processor',
        );
    }

    private function createMetadataOperation(): Post
    {
        return new Post(
            uriTemplate: '/files_operations/metadata',
            inputFormats: ['json' => ['application/json']],
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER') and is_granted('contao_user.fop.f2')",
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
