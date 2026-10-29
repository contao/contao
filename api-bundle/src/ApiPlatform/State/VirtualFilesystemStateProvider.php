<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemItemFactory;
use Contao\ApiBundle\Dto\VirtualFilesystemLocation;
use Contao\ApiBundle\Exception\ResourceNotFoundException;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements ProviderInterface<VirtualFilesystemItem>
 */
final class VirtualFilesystemStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly VirtualFilesystemRegistry $filesystems,
        private readonly VirtualFilesystemItemFactory $itemFactory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object
    {
        $filesystem = $this->filesystems->get($operation->getExtraProperties()['contao']['filesystem']);

        try {
            if ($operation instanceof CollectionOperationInterface) {
                return $this->provideCollection($context, $filesystem);
            }

            $location = VirtualFilesystemLocation::fromArray($uriVariables)->toLocation();

            if (!$item = $filesystem->get($location)) {
                throw new ResourceNotFoundException('The requested file or directory does not exist.');
            }

            return $this->itemFactory->create($item);
        } catch (UnableToResolveUuidException $exception) {
            throw new ResourceNotFoundException('The requested file or directory does not exist.', previous: $exception);
        }
    }

    /**
     * @return list<VirtualFilesystemItem>
     */
    private function provideCollection(array $context, VirtualFilesystemInterface $filesystem): array
    {
        $filters = $this->getFilters($context);
        $selector = array_intersect_key($filters, ['path' => true, 'uuid' => true]);
        $path = $selector ? VirtualFilesystemLocation::fromArray($selector)->toLocation() : '';
        $deep = filter_var($filters['deep'] ?? false, FILTER_VALIDATE_BOOL);
        $items = $filesystem->listContents($path, $deep);

        return array_map($this->itemFactory->create(...), $items->toArray());
    }

    private function getFilters(array $context): array
    {
        $filters = $context['filters'] ?? [];
        $request = $context['request'] ?? null;

        if ($request instanceof Request) {
            $filters += $request->query->all();
        }

        return \is_array($filters) ? $filters : [];
    }
}
