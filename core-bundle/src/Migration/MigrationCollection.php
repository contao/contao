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

class MigrationCollection
{
    /**
     * @var MigrationInterface[]
     */
    private $migrations;

    /**
     * @param MigrationInterface[] $migrations
     */
    public function __construct(iterable $migrations)
    {
        $this->migrations = $migrations;
    }

    /**
     * @return MigrationInterface[]
     */
    public function getPending(): iterable
    {
        foreach ($this->migrations as $migration) {
            if ($migration->shouldRun()) {
                yield $migration;
            }
        }
    }

    /**
     * @return string[]
     */
    public function getPendingNames(): iterable
    {
        foreach ($this->getPending() as $migration) {
            yield $migration->getName();
        }
    }

    /**
     * @return MigrationResult[]
     */
    public function run(): iterable
    {
        foreach ($this->getPending() as $migration) {
            yield $migration->run();
        }
    }
}
