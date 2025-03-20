<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Symfony\Component\Filesystem\Path;

/**
 * @experimental
 */
class ElementsOperationContext
{
    private readonly FilesystemItemIterator $filesystemItems;

    private readonly string $viewPath;

    /**
     * @param list<string> $paths
     *
     * @internal
     */
    public function __construct(
        array $paths,
        private readonly VirtualFilesystemInterface $storage,
    ) {
        if (0 === \count($paths)) {
            throw new \InvalidArgumentException('There needs to be at least one path.');
        }

        $this->filesystemItems = new FilesystemItemIterator(
            array_map(
                fn (string $path): FilesystemItem => $this->storage->get($path),
                $paths,
            ),
        );

        $this->viewPath = Path::getDirectory($paths[0]);
    }

    public function getFilesystemItems(): FilesystemItemIterator
    {
        return $this->filesystemItems;
    }

    public function hasMixedTypes(): bool
    {
        $filesystemItems = $this->getFilesystemItems();
        $isFile = $filesystemItems->first()?->isFile();

        foreach ($filesystemItems as $filesystemItem) {
            if ($filesystemItem->isFile() !== $isFile) {
                return true;
            }
        }

        return false;
    }

    public function getStorage(): VirtualFilesystemInterface
    {
        return $this->storage;
    }

    public function getViewPath(): string
    {
        return $this->viewPath;
    }
}
