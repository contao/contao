<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @experimental
 */
class DbafsChangeEvent
{
    /**
     * @interal
     */
    public function __construct(private readonly ChangeSet $changeSet)
    {
    }

    public function getChangeSet(): ChangeSet
    {
        return $this->changeSet;
    }

    public function getCreatedFilesystemItems(VirtualFilesystem $storage): FilesystemItemIterator
    {
        return new FilesystemItemIterator($this->listCreatedFilesystemItems($storage));
    }

    public function getUpdatedFilesystemItems(VirtualFilesystem $storage, bool $includeLastModified = false): FilesystemItemIterator
    {
        return new FilesystemItemIterator($this->listUpdatedFilesystemItems($storage, $includeLastModified));
    }

    public function getDeletedFilesystemItems(VirtualFilesystem $storage): FilesystemItemIterator
    {
        return new FilesystemItemIterator($this->listDeletedFilesystemItems($storage));
    }

    /**
     * @return \Generator<FilesystemItem>
     */
    private function listCreatedFilesystemItems(VirtualFilesystem $storage): \Generator
    {
        foreach ($this->changeSet->getItemsToCreate() as $itemToCreate) {
            if (null !== ($path = $this->match($itemToCreate->getPath(), $storage))) {
                yield $storage->get($path);
            }
        }
    }

    /**
     * @return \Generator<FilesystemItem>
     */
    private function listUpdatedFilesystemItems(VirtualFilesystem $storage, bool $includeLastModified): \Generator
    {
        foreach ($this->changeSet->getItemsToUpdate($includeLastModified) as $itemToUpdate) {
            $item = null;

            if ($itemToUpdate->updatesPath() && null !== ($path = $this->match($itemToUpdate->getNewPath(), $storage))) {
                yield $item = $storage->get($path);
            }

            if (null !== ($path = $this->match($itemToUpdate->getExistingPath(), $storage))) {
                if (!$itemToUpdate->updatesPath()) {
                    yield $storage->get($path);
                } elseif (null !== ($type = $item?->isFile())) {
                    yield new FilesystemItem($type, $path);
                }
            }
        }
    }

    /**
     * @return \Generator<FilesystemItem>
     */
    private function listDeletedFilesystemItems(VirtualFilesystem $storage): \Generator
    {
        foreach ($this->changeSet->getItemsToDelete() as $itemToDelete) {
            if (null !== ($path = $this->match($itemToDelete->getPath(), $storage))) {
                yield new FilesystemItem($itemToDelete->isFile(), $path);
            }
        }
    }

    private function match(string $path, VirtualFilesystem $storage): string|null
    {
        if (!Path::isBasePath($storage->getPrefix(), $path)) {
            return null;
        }

        return Path::makeRelative($path, $storage->getPrefix());
    }
}
