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
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\Dto\DataContainerRecord;

final class DataContainerResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly string $apiPrefix,
        private readonly string $dataContainerApiPrefix,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (DataContainerRecord::class !== $resourceClass) {
            return $this->decorated->create($resourceClass);
        }

        $apiResource = new ApiResource()
            ->withClass(DataContainerRecord::class)
            ->withShortName('Content')
            ->withRoutePrefix($this->getRoutePrefix('tl_content'))
            ->withExtraProperties([
                'contao' => [
                    'table' => 'tl_content',
                ],
            ])
            ->withOperations(new Operations([
                'get_collection' => new GetCollection()
                    ->withClass(DataContainerRecord::class)
                    ->withShortName('Content')
                    ->withUriTemplate($this->getRoutePrefix('tl_content')),
                'get' => new Get()
                    ->withClass(DataContainerRecord::class)
                    ->withShortName('Content')
                    ->withUriTemplate($this->getRoutePrefix('tl_content').'/{id}'),
                'post' => new Post()
                    ->withClass(DataContainerRecord::class)
                    ->withShortName('Content')
                    ->withUriTemplate($this->getRoutePrefix('tl_content')),
                'patch' => new Patch()
                    ->withClass(DataContainerRecord::class)
                    ->withShortName('Content')
                    ->withUriTemplate($this->getRoutePrefix('tl_content').'/{id}'),
                'delete' => new Delete()
                    ->withClass(DataContainerRecord::class)
                    ->withShortName('Content')
                    ->withUriTemplate($this->getRoutePrefix('tl_content').'/{id}'),
            ]))
        ;

        return new ResourceMetadataCollection($resourceClass, [$apiResource]);
    }

    private function getRoutePrefix(string $table): string
    {
        return '/'.trim($this->apiPrefix, '/').'/'.trim($this->dataContainerApiPrefix, '/').'/'.$table;
    }
}
