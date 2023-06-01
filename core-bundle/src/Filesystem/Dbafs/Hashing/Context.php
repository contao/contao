<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs\Hashing;

/**
 * @experimental
 */
final class Context
{
    private int|null $newLastModified;
    private bool|string|null $result = false;

    /**
     * @internal
     */
    public function __construct(
        private string|null $oldHash = null,
        private int|null $oldLastModified = null,
    ) {
        $this->newLastModified = $oldLastModified;
    }

    public function canSkipHashing(): bool
    {
        return null !== $this->oldHash;
    }

    public function skipHashing(): void
    {
        if (!$this->canSkipHashing()) {
            throw new \LogicException('Hashing may not be skipped for the current resource.');
        }

        $this->result = null;
    }

    public function setHash(string $hash): void
    {
        $this->result = $hash;
    }

    public function getLastModified(): int|null
    {
        return $this->newLastModified ?? $this->oldLastModified;
    }

    public function updateLastModified(int|null $lastModified): void
    {
        $this->newLastModified = $lastModified;
    }

    public function lastModifiedChanged(): bool
    {
        return $this->oldLastModified !== $this->newLastModified;
    }

    public function getResult(): string
    {
        if (false === $this->result) {
            throw new \LogicException('No result has been set for this hashing context.');
        }

        return $this->result ?? $this->oldHash;
    }
}
