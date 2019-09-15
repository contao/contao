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

    /** @var int */
    private $databaseBulkInsertSize = 100;

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
    public function sync(string $scope = '', bool $dryRun = false): ChangeSet
    {
        // todo: throw/handle invalid scope

        // Gather all data needed for diffing
        $fsPaths = iterator_to_array($this->storage->listSynchronizablePaths($scope));
        $fsHashLookup = $this->fileHashProvider->getHashes($fsPaths);

        if (!$dryRun) {
            $this->connection->executeQuery('LOCK TABLES tl_files WRITE');
            $this->connection->beginTransaction();
        }

        [$dbPaths, $dbHashLookup, $uuidLookup] = $this->getDatabaseEntries($scope);

        // Compute and apply change set
        $changeSet = $this->computeChangeSet($fsPaths, $fsHashLookup, $dbPaths, $dbHashLookup, $scope);

        if (!$dryRun) {
            $this->applyDatabaseChanges($changeSet, $uuidLookup);

            $this->connection->commit();
            $this->connection->executeQuery('UNLOCK TABLES');
        }

        return $changeSet;
    }

    public function getDatabaseBulkInsertSize(): int
    {
        return $this->databaseBulkInsertSize;
    }

    public function setDatabaseBulkInsertSize(int $databaseBulkInsertSize): void
    {
        $this->databaseBulkInsertSize = $databaseBulkInsertSize;
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

    /**
     * Return full hash lookup [path → hash] and UUID lookup [path → UUID]
     * of the currently stored file tree. If a directory is specified, the
     * returned list will contain paths in this directory as well as all
     * parent directories.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getDatabaseEntries(string $subDirectory): array
    {
        $searchPath = $this->uploadPath;

        $parentDirectories = [];
        $subDirectory = rtrim($subDirectory, '/');

        if ('' !== $subDirectory) {
            $searchPath .= '/'.$subDirectory;

            // add parent paths
            do {
                $parentDirectories[] = $this->uploadPath.'/'.$subDirectory;
            } while ('.' !== ($subDirectory = \dirname($subDirectory)));
        }

        $items = $this->connection
            ->executeQuery(
                "SELECT path, uuid, hash, IF(type='folder', 1, 0) AS is_folder, IF(path LIKE ? OR (path IN (?) AND type='folder'), 1, 0) AS is_included FROM tl_files",
                [$searchPath.'/%', $parentDirectories],
                [\PDO::PARAM_STR, Connection::PARAM_STR_ARRAY]
            )
            ->fetchAll(FetchMode::NUMERIC)
        ;

        $dbPaths = [];
        $hashLookup = [];
        $uuidLookup = [];

        foreach ($items as [$path, $uuid, $hash, $isDir, $isIncluded]) {
            $path = $this->convertToNormalizedPath($path, $this->uploadPath, (bool) $isDir);

            $hashLookup[$path] = $hash;
            $uuidLookup[$path] = $uuid;

            if ((bool) $isIncluded) {
                $dbPaths[] = $path;
            }
        }

        return [$dbPaths, $hashLookup, $uuidLookup];
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

        // INSERT
        $inserts = [];
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

            // todo: test needed for inserting with size=0 vs. size>0
            if (0 === $this->databaseBulkInsertSize) {
                $this->connection->insert('tl_files', $dataToInsert);
            } else {
                $inserts[] = array_values($dataToInsert);
            }
        }
        if (!empty($inserts)) {
            foreach (array_chunk($inserts, $this->databaseBulkInsertSize) as $chunk) {
                $placeholders = implode(', ', array_fill(0, \count($chunk), '(?, ?, ?, ?, ?, ?, ?, ?)'));
                $data = array_merge(...$chunk);
                $this->connection->executeQuery('INSERT INTO tl_files (`uuid`, `pid`, `path`, `hash`, `name`, `extension`, `type`, `tstamp`) VALUES '.$placeholders, $data);
            }
        }

        // UPDATE
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

        // DELETE
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

        return $path.($isDir ? '/' : '');
    }

    private function convertToDatabasePath(string $path): string
    {
        return Path::join([$this->uploadPath, $path]);
    }

    private function generateUuid(): string
    {
        // todo: merge/replace with Database::getInstance()->getUuid()
        //       Do we really need the database for UUID generation? - maybe use `ramsey/uuid`
        static $uuids = [];

        if (empty($uuids)) {
            $uuids = $this->connection
                ->executeQuery(implode(' UNION ALL ', array_fill(0, 100, "SELECT UNHEX(REPLACE(UUID(), '-', '')) AS uuid")))
                ->fetchAll(FetchMode::COLUMN)
            ;
        }

        return array_pop($uuids);
    }

    private function getParentUuid($path, &$uuidLookup): ?string
    {
        $parentPath = \dirname($path).'/';
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
