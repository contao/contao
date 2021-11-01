<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup\Config;

use Contao\CoreBundle\Doctrine\Backup\Backup;

abstract class AbstractConfig
{
    private Backup $backup;
    private array $tablesToIgnore = [];
    private bool $gzCompression;

    public function __construct(Backup $backup)
    {
        $this->backup = $backup;

        // Enable gz compression by default if path ends on .gz
        $this->gzCompression = 0 === strcasecmp(substr($backup->getFilepath(), -3), '.gz');
    }

    public function getTablesToIgnore(): array
    {
        return $this->tablesToIgnore;
    }

    public function getBackup(): Backup
    {
        return $this->backup;
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
        $new->backup = new Backup($filePath);

        return $new;
    }
}
