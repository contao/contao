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

use Contao\Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Webmozart\PathUtil\Path;

class Dbafs
{
    /** @var DbafsStorageInterface */
    private $storage;

    /** @var FileHashProviderInterface */
    private $fileHashProvider;

    /** @var Connection */
    private $connection;

    /** @var string */
    private $uploadPath;

    public function __construct(DbafsStorageInterface $storage, FileHashProviderInterface $fileHashProvider, Connection $connection, string $uploadPath)
    {
        $this->storage = $storage;
        $this->fileHashProvider = $fileHashProvider;
        $this->connection = $connection;

        // todo: Find out if we can omit this smh - it's currently only needed
        //       because of the current 'files/' prefix in the database; it's
        //       implied in the storage (via filesystem)...
        $this->uploadPath = $uploadPath;
    }

    /**
     * Sync the database with the filesystem by comparing and applying changes.
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function sync(bool $dryRun = false): ChangeSet
    {
        // Gather all data needed for diffing
        $fsPaths = iterator_to_array($this->storage->listSynchronizablePaths());
        $fsHashLookup = $this->fileHashProvider->getHashes($fsPaths);

        if (!$dryRun) {
            $this->connection->executeQuery('LOCK TABLES tl_files WRITE');
            $this->connection->beginTransaction();
        }

        [$dbHashLookup, $uuidLookup] = $this->getDatabaseEntries();

        // Compute and apply change set
        $changeSet = $this->computeChangeSet($fsPaths, $fsHashLookup, $dbHashLookup);

        if (!$dryRun) {
            $this->applyDatabaseChanges($changeSet, $uuidLookup);

            $this->connection->commit();
            $this->connection->executeQuery('UNLOCK TABLES');
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
    private function computeChangeSet(array &$fsPaths, array &$fsHashLookup, array &$dbHashLookup): ChangeSet
    {
        $itemsToCreate = [];
        $itemsToUpdate = [];

        // Mark all items to be deleted fist and successively remove them once found.
        $itemsToDelete = $dbHashLookup;

        // Lookup data structure [directory path → [child hash + name, ...]] that
        // contains all parts to compute a directory's hash. Because we're iterating
        // over children first, folder hashes can be computed on the fly.
        $dirHashesParts = [];

        foreach ($fsPaths as $path) {
            $parentDir = \dirname($path) . '/';

            if (!isset($dirHashesParts[$parentDir])) {
                $dirHashesParts[$parentDir] = [];
            }

            // Obtain hash for comparison
            if ($this->isDirectory($path)) {
                $childHashes = $dirHashesParts[$path] ?? [];
                ksort($childHashes);
                unset($dirHashesParts[$path]);

                // Compute directory hash
                $hash = md5(implode("\0", $childHashes));
            } else {
                $hash = $fsHashLookup[$path];
            }

            $name = $this->getFilename($path);
            $dirHashesParts[$parentDir][$name] = $hash . $name;

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
        foreach ($itemsToCreate as $path => $dataToInsert) {
            $candidates = array_keys($itemsToDelete, $dataToInsert[ChangeSet::ATTRIBUTE_HASH], true);

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
        }

        // Sort and clean item lists
        $itemsToCreate = array_values(array_reverse($itemsToCreate));
        $itemsToUpdate = array_reverse($itemsToUpdate);
        $itemsToDelete = array_keys($itemsToDelete);

        return new ChangeSet($itemsToCreate, $itemsToUpdate, $itemsToDelete);
    }

    /**
     * Return full hash lookup [path → hash] and UUID lookup [path → UUID]
     * of the currently stored file tree.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getDatabaseEntries(): array
    {
        // todo: check what we would need for partial sync (all references?) or remove
        $basePath = $this->uploadPath;
        $parentDirectories = [];

        $items = $this->connection
            ->executeQuery(
                "SELECT path, uuid, hash, IF(type='folder', 1, 0) FROM tl_files WHERE path LIKE ? OR path IN (?)",
                [$basePath . '/%', $parentDirectories],
                [\PDO::PARAM_STR, Connection::PARAM_STR_ARRAY]
            )
            ->fetchAll(FetchMode::NUMERIC);

        $hashLookup = [];
        $uuidLookup = [];

        foreach ($items as [$path, $uuid, $hash, $isDir]) {
            $path = $this->convertToNormalizedPath($path, $basePath, (bool)$isDir);

            $hashLookup[$path] = $hash;
            $uuidLookup[$path] = $uuid;
        }

        return [$hashLookup, $uuidLookup];
    }

    /**
     * Apply changes (create / update / delete) to the database. In order to
     * apply fast hierarchy changes a full UUID lookup [path → UUID] must be
     * specified.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    private function applyDatabaseChanges(ChangeSet $changeSet, array &$uuidLookup): void
    {
        $currentTime = time();

        foreach ($changeSet->getItemsToCreate() as $newValues) {
            $newUuid = $this->generateUuid();
            $newPath = $newValues[ChangeSet::ATTRIBUTE_PATH];
            $isDir = $this->isDirectory($newPath);

            if ($isDir) {
                // add new UUID to lookup, so that child entries will be able to reference it
                $uuidLookup[$newPath] = $newUuid;
            }

            $dataToInsert = [
                'uuid' => $newUuid,
                'pid' => $this->getParentUuid($newPath, $uuidLookup),
                'path' => $this->convertToDatabasePath($newPath),
                'hash' => $newValues[ChangeSet::ATTRIBUTE_HASH],
                'name' => Path::getFilename($newPath),
                'extension' => !$isDir ? Path::getExtension($newPath) : '',
                'type' => $isDir ? 'folder' : 'file',
                'tstamp' => $currentTime,
            ];

            $this->connection->insert(
                'tl_files',
                $dataToInsert
            );
        }

        foreach ($changeSet->getItemsToUpdate() as $pathIdentifier => $changedValues) {
            $dataToUpdate = [
                'tstamp' => $currentTime,
            ];

            if ($changedValues[ChangeSet::ATTRIBUTE_PATH]) {
                $dataToUpdate['path'] = $this->convertToDatabasePath($changedValues[ChangeSet::ATTRIBUTE_PATH]);
                $dataToUpdate['pid'] = $this->getParentUuid($pathIdentifier, $uuidLookup);
            }

            if ($changedValues[ChangeSet::ATTRIBUTE_HASH]) {
                $dataToUpdate['hash'] = $changedValues[ChangeSet::ATTRIBUTE_HASH];
            }

            $this->connection->update(
                'tl_files',
                $dataToUpdate,
                ['path' => $this->convertToDatabasePath($pathIdentifier)]
            );
        }

        foreach ($changeSet->getItemsToDelete() as $pathToDelete) {
            $this->connection->delete(
                'tl_files',
                ['path' => $this->convertToDatabasePath($pathToDelete)]
            );
        }
    }

    private function isDirectory(string $path): bool
    {
        return '/' === substr($path, -1);
    }

    private function getFilename(string $path): string
    {
        return Path::getFilename($path);
    }

    private function convertToNormalizedPath(string $path, string $basePathToRemove, bool $isDir): string
    {
        $path = Path::makeRelative($path, $basePathToRemove);

        return $path . ($isDir ? '/' : '');
    }

    private function convertToDatabasePath(string $path): string
    {
        return Path::join([$this->uploadPath, $path]);
    }

    private function generateUuid(): string
    {
        // todo: replace (we should not need the database here?)
        return Database::getInstance()->getUuid();
    }

    private function getParentUuid($path, &$uuidLookup): ?string
    {
        $parentPath = \dirname($path) . '/';
        if ('./' === $parentPath) {
            return null;
        }

        $pid = $uuidLookup[$parentPath] ?? null;
        if (null !== $pid) {
            return $pid;
        }

        throw new \RuntimeException("No parent entry found for non-root resource '$path'.");
    }
}
