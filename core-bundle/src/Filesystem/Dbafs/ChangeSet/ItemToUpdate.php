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
    private string $existingPath;
    private ?string $newHash;
    private ?string $newPath;

    /**
     * @var false|int|null
     */
    private $lastModified;

    /**
     * @param int|false|null $lastModified
     *
     * @internal
     */
    public function __construct(string $existingPath, ?string $newHash, ?string $newPath, $lastModified = false)
    {
        $this->existingPath = $existingPath;
        $this->newHash = $newHash;
        $this->newPath = $newPath;
        $this->lastModified = $lastModified;
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
            throw new \LogicException(sprintf('The update to item "%s" does not include a new hash.', $this->existingPath));
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
            throw new \LogicException(sprintf('The update to item "%s" does not include a new path.', $this->existingPath));
        }

        return $this->newPath;
    }

    public function updatesLastModified(): bool
    {
        return false !== $this->lastModified;
    }

    public function getLastModified(): ?int
    {
        if (false === $this->lastModified) {
            throw new \LogicException(sprintf('The update to item "%s" does not include a last modified date.', $this->existingPath));
        }

        return $this->lastModified;
    }
}
