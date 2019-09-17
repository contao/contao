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

class DbafsDatabase implements DbafsDatabaseInterface
{
    /** @var Connection */
    private $connection;

    /** @var string */
    private $uploadPath;

    /** @var int */
    private $databaseBulkInsertSize = 100;

    public function __construct(Connection $connection, string $uploadPath)
    {
        $this->connection = $connection;
        $this->uploadPath = $uploadPath;
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
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->connection->executeQuery('LOCK TABLES tl_files WRITE');
        $this->connection->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $this->connection->commit();
        $this->connection->executeQuery('UNLOCK TABLES');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseEntries(string $scope = ''): array
    {
        $searchPath = $this->uploadPath;

        $parentDirectories = [];
        $scope = rtrim($scope, '/');

        if ('' !== $scope) {
            $searchPath .= '/'.$scope;

            // add parent paths
            do {
                $parentDirectories[] = $this->uploadPath.'/'.$scope;
            } while ('.' !== ($scope = \dirname($scope)));
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
            $path = self::convertToNormalizedPath($path, $this->uploadPath, (bool) $isDir);

            $hashLookup[$path] = $hash;
            $uuidLookup[$path] = $uuid;

            if ((bool) $isIncluded) {
                $dbPaths[] = $path;
            }
        }

        return [$dbPaths, $hashLookup, $uuidLookup];
    }

    /**
     * {@inheritdoc}
     */
    public function applyDatabaseChanges(ChangeSet $changeSet, array &$uuidLookup): void
    {
        $currentTime = time();

        // INSERT
        $inserts = [];
        foreach ($changeSet->getItemsToCreate() as $newValues) {
            $newUuid = $this->generateUuid();
            $newPath = $newValues[ChangeSet::ATTRIBUTE_PATH];
            $isDir = self::isDirectory($newPath);

            if ($isDir) {
                // add new UUID to lookup, so that child entries will be able to reference it
                $uuidLookup[$newPath] = $newUuid;
            }

            $dataToInsert = [
                'uuid' => $newUuid,
                'pid' => self::getParentUuid($newPath, $uuidLookup),
                'path' => $this->convertToDatabasePath($newPath),
                'hash' => $newValues[ChangeSet::ATTRIBUTE_HASH],
                'name' => Path::getFilename($newPath),
                'extension' => !$isDir ? Path::getExtension($newPath) : '',
                'type' => $isDir ? 'folder' : 'file',
                'tstamp' => $currentTime,
            ];

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
                $dataToUpdate['pid'] = self::getParentUuid($pathIdentifier, $uuidLookup);
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

    private static function isDirectory(string $path): bool
    {
        return '/' === substr($path, -1);
    }

    private static function convertToNormalizedPath(string $path, string $basePathToRemove, bool $isDir): string
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

    private static function getParentUuid($path, &$uuidLookup): ?string
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
