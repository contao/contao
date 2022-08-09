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
     * @var iterable<MigrationInterface>
     */
    private iterable $migrations;

    /**
     * @param iterable<MigrationInterface> $migrations
     */
    public function __construct(iterable $migrations)
    {
        $this->migrations = $migrations;
    }

    public function hasPending(): bool
    {
        foreach ($this->migrations as $migration) {
            if ($migration->shouldRun()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return iterable<MigrationInterface>
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
     * @return iterable<string>
     */
    public function getPendingNames(): iterable
    {
        foreach ($this->getPending() as $migration) {
            yield $migration->getName();
        }
    }

    /**
     * @return iterable<MigrationResult>
     */
    public function run(): iterable
    {
        foreach ($this->getPending() as $migration) {
            yield $migration->run();
        }
    }
}
