<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

class Migrations
{
    /**
     * @var MigrationInterface[]
     */
    private $migrations = [];

    /**
     * @param MigrationInterface[] $migrations
     */
    public function __construct(iterable $migrations)
    {
        $this->migrations = $migrations;
    }

    /**
     * @return string[]
     */
    public function getPendingMigrations(): iterable
    {
        foreach ($this->migrations as $migration) {
            if ($migration->shouldRun()) {
                yield $migration->getName();
            }
        }
    }

    /**
     * @return MigrationResult[]
     */
    public function runMigrations(): iterable
    {
        foreach ($this->migrations as $migration) {
            if ($migration->shouldRun()) {
                yield $migration->run();
            }
        }
    }
}
