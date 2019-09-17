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

use Webmozart\PathUtil\Path;

class Dbafs
{
    /** @var DbafsStorageInterface */
    private $storage;

    /** @var FileHashProviderInterface */
    private $fileHashProvider;

    /** @var DbafsDatabaseInterface */
    private $database;

    public function __construct(DbafsStorageInterface $storage, FileHashProviderInterface $fileHashProvider, DbafsDatabaseInterface $database)
    {
        $this->storage = $storage;
        $this->fileHashProvider = $fileHashProvider;
        $this->database = $database;
    }

    /**
     * Sync the database with the filesystem by comparing and applying changes.
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function sync(string $scope = '', bool $dryRun = false): ChangeSet
    {
        // todo: throw/handle invalid scope

        // Gather all data needed for diffing
        $fsPaths = iterator_to_array($this->storage->listSynchronizablePaths($scope));
        $fsHashLookup = $this->fileHashProvider->getHashes($fsPaths);

        if (!$dryRun) {
            $this->database->beginTransaction();
        }

        // todo: find sth better than array of arrays
        //       maybe do not pass around uuidLookup at all (only used inside database / factory?)
        [$dbPaths, $dbHashLookup, $uuidLookup] = $this->database->getDatabaseEntries($scope);

        // Compute and apply change set
        $changeSet = $this->computeChangeSet($fsPaths, $fsHashLookup, $dbPaths, $dbHashLookup, $scope);

        if (!$dryRun) {
            $this->database->applyDatabaseChanges($changeSet, $uuidLookup);
            $this->database->commit();
        }

        return $changeSet;
    }

    /**
     * Compare filesystem items with database entries by path and hash and
     * compute a set of changes that should be applied to the database to
     * sync it up again.
     *
     * Filesystem paths must be specified in the order of most to least
     * specific (= children before parent folders).
     *
     * Contrary the computed change set for creation and updates will be
     * in the order from least specific to most specific paths (to allow
     * efficient updating of parent-child references).
     */
    private function computeChangeSet(array &$fsPaths, array &$fsHashLookup, array &$dbPaths, array &$dbHashLookup, string $scope): ChangeSet
    {
        $isPartialSync = \count($dbPaths) !== \count($dbHashLookup);
        if ($isPartialSync && '' === rtrim($scope, '/')) {
            throw new \InvalidArgumentException('Scope cannot be empty in partial sync.');
        }

        $itemsToCreate = [];
        $itemsToUpdate = [];

        // Mark all items to be deleted fist and successively remove them once found.
        $itemsToDelete = array_flip($dbPaths);

        // Lookup data structure [directory path → [child hash + name, ...]] that
        // contains the traversed child hashes + names to compute a directory's hash.
        $dirHashesParts = [];

        foreach ($fsPaths as $path) {
            // Obtain hash for comparison
            if ($this->isDirectory($path)) {
                $childHashes = $dirHashesParts[$path] ?? [];

                if ($isPartialSync && !Path::isBasePath($scope, $path)) {
                    // In partial sync we need to manually add other child hashes as we did not traverse them.
                    $pattern = "@^{$path}[^/]+[.]*[/]?\$@";
                    $missingChildren = array_filter($dbHashLookup, function ($path) use ($pattern, &$childHashes) {
                        return preg_match($pattern, $path) && !isset($childHashes[$this->getFilename($path)]);
                    }, ARRAY_FILTER_USE_KEY);

                    foreach ($missingChildren as $childPath => $childHash) {
                        $childName = $this->getFilename($childPath);
                        $childHashes[$childName] = $childHash.$childName;
                    }
                }

                ksort($childHashes);
                unset($dirHashesParts[$path]);

                // Compute directory hash
                $hash = md5(implode("\0", $childHashes));
            } else {
                $hash = $fsHashLookup[$path];
            }

            // Store hash + name for parent directory hash
            $parentDir = \dirname($path).'/';
            $name = $this->getFilename($path);

            if (!isset($dirHashesParts[$parentDir])) {
                $dirHashesParts[$parentDir] = [];
            }
            $dirHashesParts[$parentDir][$name] = $hash.$name;

            // Determine changes
            if (!isset($dbHashLookup[$path])) {
                // Resource wasn't found (or moved), mark to create
                $itemsToCreate[$path] = [ChangeSet::ATTRIBUTE_HASH => $hash, ChangeSet::ATTRIBUTE_PATH => $path];
            } elseif ($hash !== $dbHashLookup[$path]) {
                // Hash has changed
                $itemsToUpdate[$path] = [ChangeSet::ATTRIBUTE_HASH => $hash];
            }

            // Remove from list of orphaned items
            unset($itemsToDelete[$path]);
        }

        // Detect moves: Check if items that should get created can be found in
        // the current list of orphans and - if so - only update their path.
        $hasMoves = false;
        foreach ($itemsToCreate as $path => $dataToInsert) {
            $candidates = array_intersect(
                array_flip($itemsToDelete),
                array_keys($dbHashLookup, $dataToInsert[ChangeSet::ATTRIBUTE_HASH], true)
            );

            if (\count($candidates) > 1) {
                // If two or more files with the same hash were moved, try
                // to identify them by their name.
                $filename = $this->getFilename($path);

                $candidates = array_filter($candidates, function ($path) use ($filename) {
                    return $filename === $this->getFilename($path);
                });
            }

            if (1 !== \count($candidates)) {
                continue;
            }

            $oldPath = reset($candidates);

            // Found move, transfer to update list
            $itemsToUpdate[$oldPath] = [ChangeSet::ATTRIBUTE_PATH => $path];
            unset($itemsToCreate[$path], $itemsToDelete[$oldPath]);

            $hasMoves = true;
        }

        // Sort and clean item lists
        if ($hasMoves) {
            ksort($itemsToUpdate, SORT_DESC);
        } else {
            $itemsToUpdate = array_reverse($itemsToUpdate);
        }

        $itemsToCreate = array_reverse(array_values($itemsToCreate));
        $itemsToDelete = array_keys($itemsToDelete);

        return new ChangeSet($itemsToCreate, $itemsToUpdate, $itemsToDelete);
    }

    private function isDirectory(string $path): bool
    {
        return '/' === substr($path, -1);
    }

    private function getFilename(string $path): string
    {
        return Path::getFilename($path);
    }
}
