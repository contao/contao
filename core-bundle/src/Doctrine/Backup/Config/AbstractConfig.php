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
    private array $tablesToIgnore = [];
    private bool $gzCompression;

    public function __construct(private Backup $backup)
    {
        // Enable gz compression by default if path ends on .gz
        $this->gzCompression = 0 === strcasecmp(substr($backup->getFilename(), -3), '.gz');
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

    public function withGzCompression(bool $enable): static
    {
        $new = clone $this;
        $new->gzCompression = $enable;

        return $new;
    }

    public function withTablesToIgnore(array $tablesToIgnore): static
    {
        $new = clone $this;
        $new->tablesToIgnore = $this->filterTablesToIgnore($new->tablesToIgnore, $tablesToIgnore);

        return $new;
    }

    public function withFileName(string $filename): static
    {
        $new = clone $this;
        $new->backup = new Backup($filename);

        return $new;
    }

    private function filterTablesToIgnore(array $currentTables, array $newTables): array
    {
        $newList = array_filter($newTables, static fn ($table) => !\in_array($table[0], ['-', '+'], true));

        if ($newList) {
            $currentTables = $newList;
        }

        foreach ($newTables as $newTable) {
            $prefix = $newTable[0];
            $table = substr($newTable, 1);

            if ('-' === $prefix && \in_array($table, $currentTables, true)) {
                unset($currentTables[array_search($table, $currentTables, true)]);
            } elseif ('+' === $prefix && !\in_array($table, $currentTables, true)) {
                $currentTables[] = $table;
            }
        }

        sort($currentTables);

        return $currentTables;
    }
}
