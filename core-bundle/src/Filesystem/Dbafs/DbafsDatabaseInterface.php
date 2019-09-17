<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

interface DbafsDatabaseInterface
{
    /**
     * Return list of paths, full hash lookup [path → hash] and UUID lookup
     * [path → UUID] of the currently stored file tree. If a directory scope
     * is specified, the returned list will contain paths in this directory
     * as well as all parent directories.
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array [dbPaths, hashLookup, uuidLookup]
     */
    public function getDatabaseEntries(string $scope = ''): array;

    /**
     * Apply changes (create / update / delete) to the database. In order to
     * apply fast hierarchy changes a full UUID lookup [path → UUID] must be
     * specified.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function applyDatabaseChanges(ChangeSet $changeSet, array &$uuidLookup): void;

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function beginTransaction(): void;

    /**
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function commit(): void;
}
