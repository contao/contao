<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Dumper;

class Config
{
    private array $tablesToIgnore = [];
    private string $targetPath;
    private bool $gzCompression;
    private int $bufferSize = 104857600; // 100 MB

    public function __construct(string $targetPath)
    {
        $this->targetPath = $targetPath;

        // Enable gz compression by default if target path ends on .gz
        $this->gzCompression = 0 === strcasecmp(substr($targetPath, -3), '.gz');
    }

    public function getTablesToIgnore(): array
    {
        return $this->tablesToIgnore;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    public function isGzCompressionEnabled(): bool
    {
        return $this->gzCompression;
    }

    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    public function withBufferSize(int $bufferSizeInBytes): self
    {
        $new = clone $this;
        $new->bufferSize = $bufferSizeInBytes;

        return $new;
    }

    public function withGzCompression(bool $enable): self
    {
        $new = clone $this;
        $new->gzCompression = $enable;

        return $new;
    }

    public function withTablesToIgnore(array $tablesToIgnore): self
    {
        $new = clone $this;
        $new->tablesToIgnore = $tablesToIgnore;

        return $new;
    }

    public function withTargetPath(string $targetPath): self
    {
        $new = clone $this;
        $new->targetPath = $targetPath;

        return $new;
    }
}
