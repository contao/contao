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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<mixed, VirtualFilesystemItem>
 */
final class VirtualFilesystemStateProcessor implements ProcessorInterface
{
    private readonly VirtualFilesystemInterface $filesStorage;

    public function __construct(
        VirtualFilesystem $filesStorage,
        Security $security,
        private readonly RequestStack $requestStack,
    ) {
        $this->filesStorage = new PermissionCheckingVirtualFilesystem($filesStorage, $security);
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): VirtualFilesystemItem
    {
        if ($data instanceof VirtualFilesystemMove) {
            return $this->move($data);
        }

        $path = $uriVariables['path'] ?? null;

        if (!\is_string($path) || '' === $path) {
            throw new BadRequestHttpException('A file path is required.');
        }

        return $this->upload($path, $context['request'] ?? $this->requestStack->getCurrentRequest());
    }

    private function move(VirtualFilesystemMove $move): VirtualFilesystemItem
    {
        if ('' === $move->source || '' === $move->destination) {
            throw new BadRequestHttpException('Source and destination paths are required.');
        }

        $this->filesStorage->move($move->source, $move->destination);

        return $this->getItem($move->destination);
    }

    private function upload(string $path, mixed $request): VirtualFilesystemItem
    {
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('An upload body is required.');
        }

        $this->filesStorage->writeStream($path, $request->getContent(true));

        return $this->getItem($path);
    }

    private function getItem(string $path): VirtualFilesystemItem
    {
        $item = $this->filesStorage->get($path);

        if (!$item) {
            throw new \LogicException(\sprintf('The filesystem item "%s" was not found after writing it.', $path));
        }

        return VirtualFilesystemItem::fromFilesystemItem($item);
    }
}
