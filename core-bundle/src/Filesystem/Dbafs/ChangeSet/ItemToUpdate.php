<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs\ChangeSet;

class ItemToUpdate
{
    /**
     * @internal
     */
    public function __construct(
        private readonly string $existingPath,
        private readonly string|null $newHash,
        private readonly string|null $newPath,
        private readonly int|false|null $lastModified = false,
    ) {
    }

    public function getExistingPath(): string
    {
        return $this->existingPath;
    }

    public function updatesHash(): bool
    {
        return null !== $this->newHash;
    }

    public function getNewHash(): string
    {
        if (null === $this->newHash) {
            throw new \LogicException(\sprintf('The update to item "%s" does not include a new hash.', $this->existingPath));
        }

        return $this->newHash;
    }

    public function updatesPath(): bool
    {
        return null !== $this->newPath;
    }

    public function getNewPath(): string
    {
        if (null === $this->newPath) {
            throw new \LogicException(\sprintf('The update to item "%s" does not include a new path.', $this->existingPath));
        }

        return $this->newPath;
    }

    public function updatesLastModified(): bool
    {
        return false !== $this->lastModified;
    }

    public function getLastModified(): int|null
    {
        if (false === $this->lastModified) {
            throw new \LogicException(\sprintf('The update to item "%s" does not include a last modified date.', $this->existingPath));
        }

        return $this->lastModified;
    }
}
