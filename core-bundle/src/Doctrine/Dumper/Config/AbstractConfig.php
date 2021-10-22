<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Dumper\Config;

abstract class AbstractConfig
{
    private array $tablesToIgnore = [];
    private string $filePath;
    private bool $gzCompression;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        // Enable gz compression by default if path ends on .gz
        $this->gzCompression = 0 === strcasecmp(substr($filePath, -3), '.gz');
    }

    public function getTablesToIgnore(): array
    {
        return $this->tablesToIgnore;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function isGzCompressionEnabled(): bool
    {
        return $this->gzCompression;
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

    public function withFilePath(string $filePath): self
    {
        $new = clone $this;
        $new->filePath = $filePath;

        return $new;
    }
}
