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
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<VirtualFilesystemItem>
 */
final class VirtualFilesystemStateProvider implements ProviderInterface
{
    private readonly VirtualFilesystemInterface $filesStorage;

    public function __construct(
        VirtualFilesystem $filesStorage,
        Security $security,
        private readonly VirtualFilesystemItemFactory $itemFactory,
    ) {
        $this->filesStorage = new PermissionCheckingVirtualFilesystem($filesStorage, $security);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($context);
        }

        $path = $uriVariables['path'] ?? null;

        if (!\is_string($path) || !$item = $this->filesStorage->get($path)) {
            throw new NotFoundHttpException('The requested file or directory does not exist.');
        }

        return $this->itemFactory->create($item);
    }

    /**
     * @return list<VirtualFilesystemItem>
     */
    private function provideCollection(array $context): array
    {
        $filters = $this->getFilters($context);
        $path = \is_string($filters['path'] ?? null) ? $filters['path'] : '';
        $deep = filter_var($filters['deep'] ?? false, FILTER_VALIDATE_BOOL);
        $items = $this->filesStorage->listContents($path, $deep);

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
