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
    private ?string $oldHash;
    private ?int $oldLastModified;
    private ?int $newLastModified;

    /**
     * @var string|false|null
     */
    private $result = false;

    /**
     * @internal
     */
    public function __construct(?string $fallback = null, ?int $oldLastModified = null)
    {
        $this->oldHash = $fallback;
        $this->oldLastModified = $this->newLastModified = $oldLastModified;
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

    public function getLastModified(): ?int
    {
        return $this->newLastModified ?? $this->oldLastModified;
    }

    public function updateLastModified(?int $lastModified): void
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
