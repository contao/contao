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
     * @param iterable<MigrationInterface> $migrations
     */
    public function __construct(private readonly iterable $migrations)
    {
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
     *
     * @throws UnexpectedPendingMigrationException
     */
    public function run(array|null $pendingNames = null): iterable
    {
        if (null === $pendingNames) {
            trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" with "pendingNames: null" is deprecated and will no longer work in Contao 6.', __METHOD__);
        }

        foreach ($this->getPending() as $migration) {
            if (null !== $pendingNames) {
                $expected = array_shift($pendingNames);
                $actual = $migration->getName();
                if ($expected !== $actual) {
                    throw new UnexpectedPendingMigrationException(\sprintf('Expected %s got "%s".', $expected ? "\"$expected\"" : 'no migration', $actual));
                }
            }

            yield $migration->run();
        }

        if ($pendingNames) {
            throw new UnexpectedPendingMigrationException(\sprintf('Expected "%s" got no migration.', array_shift($pendingNames)));
        }
    }
}
