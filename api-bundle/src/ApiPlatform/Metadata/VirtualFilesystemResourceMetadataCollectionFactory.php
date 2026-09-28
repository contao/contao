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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemStateProvider;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;

final class VirtualFilesystemResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
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
            provider: VirtualFilesystemStateProvider::class,
        );
    }

    private function createGetOperation(): Get
    {
        return new Get(
            uriTemplate: '/files/{path}',
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            requirements: ['path' => '.+'],
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
            provider: VirtualFilesystemStateProvider::class,
        );
    }

    private function createUploadOperation(): Put
    {
        return new Put(
            uriTemplate: '/files/{path}',
            inputFormats: ['binary' => ['application/octet-stream']],
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            requirements: ['path' => '.+'],
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
            read: false,
            deserialize: false,
            processor: VirtualFilesystemStateProcessor::class,
        );
    }

    private function createMoveOperation(): Post
    {
        return new Post(
            uriTemplate: '/files/move',
            shortName: 'File',
            class: VirtualFilesystemItem::class,
            defaults: ['_scope' => 'backend'],
            security: "is_granted('ROLE_USER')",
            input: VirtualFilesystemMove::class,
            read: false,
            status: 200,
            processor: VirtualFilesystemStateProcessor::class,
        );
    }
}
