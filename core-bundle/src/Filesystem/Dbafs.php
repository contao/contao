<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Event\DbafsMetadataEvent;
use Doctrine\DBAL\Connection;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @phpstan-type ExtraMetadata array<string, mixed>
 * @phpstan-type Record array{isFile: bool, path: string, extra: ExtraMetadata}
 */
class Dbafs implements ResetInterface
{
    public const FILE_MARKER_EXCLUDED = '.nosync';
    public const FILE_MARKER_PUBLIC = '.public';

    private Connection $connection;
    private EventDispatcherInterface $eventDispatcher;

    private string $table;
    private string $hashAlgorithm;
    private string $dbPathPrefix = '';
    private int $maxFileSize = 2147483648; // 2 GiB
    private int $bulkInsertSize = 100;

    /**
     * @var array<string, array|null>
     * @phpstan-var array<string, Record|null>
     */
    private array $records = [];

    /**
     * @var array<string, string|null>
     */
    private array $pathByUuid = [];

    /**
     * @var array<int, string|null>
     */
    private array $pathById = [];

    public function __construct(Connection $connection, EventDispatcherInterface $eventDispatcher, string $table, string $hashAlgorithm = 'md5')
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;

        $this->table = $connection->quoteIdentifier($table);
        $this->hashAlgorithm = $hashAlgorithm;

        if (!\in_array($hashAlgorithm, $supportedHashAlgorithms = hash_algos(), true)) {
            throw new \InvalidArgumentException(sprintf("The '%s' hash algorithm isn't available on this system. Try '%s' instead.", $hashAlgorithm, implode("' or '", $supportedHashAlgorithms)));
        }
    }

    /**
     * @internal
     *
     * This is used as a BC layer, do not use a prefix in your own DBAFS tables
     */
    public function setDatabasePathPrefix(string $prefix): void
    {
        $this->dbPathPrefix = Path::canonicalize($prefix);
    }

    public function setMaxFileSize(int $bytes): void
    {
        $this->maxFileSize = $bytes;
    }

    public function setBulkInsertSize(int $chunkSize): void
    {
        $this->bulkInsertSize = $chunkSize;
    }

    /**
     * @param string $uuid A UUID in binary form
     */
    public function getPathFromUuid(string $uuid): ?string
    {
        if (!\array_key_exists($uuid, $this->pathByUuid)) {
            $this->loadRecordByUuid($uuid);
        }

        return $this->pathByUuid[$uuid];
    }

    public function getPathFromId(int $id): ?string
    {
        if (!\array_key_exists($id, $this->pathById)) {
            $this->loadRecordById($id);
        }

        return $this->pathById[$id];
    }

    /**
     * @phpstan-return Record|null
     */
    public function getRecord(string $path): ?array
    {
        if (!\array_key_exists($path, $this->records)) {
            $this->loadRecordByPath($path);
        }

        return $this->records[$path];
    }

    /**
     * @return \Generator<array>
     * @phpstan-return \Generator<Record>
     */
    public function getRecords(string $path, bool $deep = false): \Generator
    {
        $path = Path::join($this->dbPathPrefix, $path);

        if ($deep) {
            $rows = $this->connection->fetchAllAssociative(
                "SELECT * FROM {$this->table} WHERE path LIKE ? ORDER BY path",
                ["$path/%"]
            );
        } else {
            $rows = $this->connection->fetchAllAssociative(
                "SELECT * FROM {$this->table} WHERE path LIKE ? AND path NOT LIKE ? ORDER BY path",
                ["$path/%", "$path/%/%"]
            );
        }

        foreach ($rows as $row) {
            $itemPath = $this->convertToFilesystemPath($row['path']);

            if (!\array_key_exists($itemPath, $this->records)) {
                $this->populateRecord($row);
            }

            yield $this->records[$itemPath];
        }
    }

    /**
     * Reset the internal record cache.
     */
    public function reset(): void
    {
        $this->records = [];
        $this->pathByUuid = [];
        $this->pathById = [];
    }

    /**
     * Synchronizes the database with a given filesystem adapter. If a $scope is
     * provided only a certain file/subdirectory will be synchronized.
     *
     * @param string ...$scope relative paths inside the filesystem root
     */
    public function sync(FilesystemAdapter $filesystem, string ...$scope): ChangeSet
    {
        [$searchPaths, $parentPaths] = $this->getNormalizedSearchPaths(...$scope);

        // Gather all needed information from the database and filesystem
        [$dbPaths, $allDbHashesByPath, $allUuidsByPath] = $this->getDatabaseEntries($searchPaths, $parentPaths);
        $filesystemIterator = $this->getFilesystemPaths($filesystem, $searchPaths, $parentPaths);

        $changeSet = $this->doComputeChangeSet($dbPaths, $allDbHashesByPath, $filesystemIterator, $searchPaths);
        $this->applyChangeSet($changeSet, $allUuidsByPath);

        // Update previously cached items
        foreach ($changeSet->getItemsToUpdate() as $path => $changes) {
            if (
                null === ($newPath = $changes[ChangeSet::ATTR_PATH] ?? null) ||
                null === ($record = $this->records[$path] ?? null)
            ) {
                continue;
            }

            $record['path'] = $newPath;
            unset($this->records[$path]);
            $this->records[$newPath] = $record;
        }

        foreach ($changeSet->getItemsToDelete() as $identifier) {
            unset($this->records[$identifier]);

            $this->pathById = array_diff($this->pathById, [$identifier]);
            $this->pathByUuid = array_diff($this->pathByUuid, [$identifier]);
        }

        return $changeSet;
    }

    /**
     * Computes the change set between the database and a given filesystem
     * adapter. If a $scope is provided only a certain file/subdirectory will
     * be taken into account.
     *
     * @param string ...$scope relative paths inside the filesystem root
     */
    public function computeChangeSet(FilesystemAdapter $filesystem, string ...$scope): ChangeSet
    {
        [$searchPaths, $parentPaths] = $this->getNormalizedSearchPaths(...$scope);

        // Gather all needed information from the database and filesystem
        [$dbPaths, $allDbHashesByPath] = $this->getDatabaseEntries($searchPaths, $parentPaths);
        $filesystemIterator = $this->getFilesystemPaths($filesystem, $searchPaths, $parentPaths);

        return $this->doComputeChangeSet($dbPaths, $allDbHashesByPath, $filesystemIterator, $searchPaths);
    }

    /**
     * @param array<int, string>              $dbPaths
     * @param array<string, string>           $allDbHashesByPath
     * @param \Generator<string, string|null> $filesystemIterator
     * @param array<int, string>              $searchPaths
     */
    private function doComputeChangeSet(array $dbPaths, array $allDbHashesByPath, \Generator $filesystemIterator, array $searchPaths): ChangeSet
    {
        // We're identifying items by their (old) path and store any detected
        // changes as an array of attributes
        /** @phpstan-var array<string, array<ChangeSet::ATTR_*, string>> $itemsToCreate */
        $itemsToCreate = [];

        /** @phpstan-var array<string, array<ChangeSet::ATTR_*, string>> $itemsToUpdate */
        $itemsToUpdate = [];

        // To detect orphans, we start with a list of all items and remove them
        // once found
        /** @var array<string, int> $itemsToDelete */
        $itemsToDelete = array_flip($dbPaths);

        // We keep a list of hashes and names of traversed child elements
        // (indexed by their directory path), so that we are later able to
        // compute the directory hash
        /** @var array<string, array<int, string>> $dirHashesParts */
        $dirHashesParts = [];

        $isPartialSync = \count($dbPaths) !== \count($allDbHashesByPath);

        foreach ($filesystemIterator as $path => $hash) {
            $name = basename($path);
            $parentDir = \dirname($path).'/';

            // Directories
            if (null === $hash) {
                $childHashes = $dirHashesParts[$path] ?? [];

                // In partial sync we need to manually add child hashes of
                // items that we do not traverse but which still contribute to
                // the directory hash
                if ($isPartialSync && !$this->inPath($path, $searchPaths)) {
                    $directChildrenPattern = sprintf('@^%s[^/]+[/]?$@', preg_quote($path, '@'));

                    foreach ($allDbHashesByPath as $childPath => $childHash) {
                        $childName = basename($childPath);

                        if (\array_key_exists($childName, $childHashes) || 1 !== preg_match($directChildrenPattern, $childPath)) {
                            continue;
                        }

                        $childHashes[$childName] = $childHash.$childName;
                    }
                }

                // Compute directory hash
                $childHashes = array_filter($childHashes);
                ksort($childHashes);
                $hash = hash($this->hashAlgorithm, implode("\0", $childHashes));

                unset($dirHashesParts[$path]);
            }

            // Remember hash and name; skip non-existent resources,
            if (false === $hash) {
                $dirHashesParts[$parentDir][$name] = null;

                continue;
            }

            $dirHashesParts[$parentDir][$name] = $hash.$name;

            // Detect changes
            if (!isset($allDbHashesByPath[$path])) {
                // Resource was not found or was moved
                $itemsToCreate[$path] = [ChangeSet::ATTR_HASH => $hash, ChangeSet::ATTR_PATH => $path];
            } elseif ($hash !== $allDbHashesByPath[$path]) {
                // Hash has changed
                $itemsToUpdate[$path] = [ChangeSet::ATTR_HASH => $hash];
            }

            unset($itemsToDelete[$path]);
        }

        // Detect moves: If items that should get created can be found in the
        // list of orphans, only update their path.
        $hasMoves = false;

        foreach ($itemsToCreate as $path => $dataToInsert) {
            $candidates = array_intersect(
                array_flip($itemsToDelete),
                array_keys($allDbHashesByPath, $dataToInsert[ChangeSet::ATTR_HASH], true)
            );

            if (\count($candidates) > 1) {
                // If two or more files with the same hash were moved, try to
                // identify them by their name.
                $candidates = array_filter(
                    $candidates,
                    static fn (string $candidatePath): bool => basename($path) === basename($candidatePath)
                );
            }

            if (1 !== \count($candidates)) {
                continue;
            }

            $oldPath = reset($candidates);

            // We identified a move, transfer to update list
            $itemsToUpdate[$oldPath] = [ChangeSet::ATTR_PATH => $path];
            unset($itemsToCreate[$path], $itemsToDelete[$oldPath]);

            $hasMoves = true;
        }

        if ($hasMoves) {
            ksort($itemsToUpdate, SORT_DESC);
        } else {
            $itemsToUpdate = array_reverse($itemsToUpdate);
        }

        return new ChangeSet(
            array_reverse(array_values($itemsToCreate)),
            $itemsToUpdate,
            array_keys($itemsToDelete)
        );
    }

    private function loadRecordByUuid(string $uuid): void
    {
        $row = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE uuid=?",
            [$uuid]
        );

        if (false === $row) {
            $this->pathByUuid[$uuid] = null;

            return;
        }

        $this->populateRecord($row);
    }

    private function loadRecordById(int $id): void
    {
        $row = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id=?",
            [$id]
        );

        if (false === $row) {
            $this->pathById[$id] = null;

            return;
        }

        $this->populateRecord($row);
    }

    private function loadRecordByPath(string $path): void
    {
        $row = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE path=?",
            [$this->convertToDatabasePath($path)]
        );

        if (false === $row) {
            $this->records[$path] = null;

            return;
        }

        $this->populateRecord($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function populateRecord(array $row): void
    {
        $path = $this->convertToFilesystemPath($row['path']);
        $isFile = 'file' === $row['type'];

        $event = new DbafsMetadataEvent($this->table, $row);
        $this->eventDispatcher->dispatch($event);

        /** @phpstan-var Record $record */
        $record = [
            'isFile' => $isFile,
            'path' => $path,
            'extra' => $event->getExtraMetadata(),
        ];

        $this->records[$path] = $record;
        $this->pathByUuid[$row['uuid']] = $path;
        $this->pathById[$row['id']] = $path;
    }

    /**
     * Updates the database from a given change set. We're using chunked inserts
     * for better performance.
     *
     * @param array<string, string> $allUuidsByPath
     */
    private function applyChangeSet(ChangeSet $changeSet, array $allUuidsByPath): void
    {
        if ($changeSet->isEmpty()) {
            return;
        }

        $getParentUuid = static function (string $path) use (&$allUuidsByPath): ?string {
            if ('./' === ($parentPath = \dirname($path).'/')) {
                return null;
            }

            if (null !== ($pid = $allUuidsByPath[$parentPath] ?? null)) {
                return $pid;
            }

            throw new \RuntimeException("No parent entry found for non-root resource '$path'.");
        };

        $this->connection->beginTransaction();

        // Inserts
        $currentTime = time();
        $inserts = [];

        foreach ($changeSet->getItemsToCreate() as $newValues) {
            $newUuid = Uuid::uuid1()->getBytes();
            $newPath = $newValues[ChangeSet::ATTR_PATH];
            $isDir = '/' === substr($newPath, -1);

            if ($isDir) {
                // Add new UUID to lookup, so that child entries will be able to reference it
                $allUuidsByPath[$newPath] = $newUuid;
            }

            $dataToInsert = [
                'uuid' => $newUuid,
                'pid' => $getParentUuid($newPath),
                'path' => $this->convertToDatabasePath($newPath),
                'hash' => $newValues[ChangeSet::ATTR_HASH],
                'name' => basename($newPath),
                'extension' => !$isDir ? Path::getExtension($newPath) : '',
                'type' => $isDir ? 'folder' : 'file',
                'tstamp' => $currentTime,
            ];

            $inserts[] = array_values($dataToInsert);
        }

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, $this->bulkInsertSize) as $chunk) {
                $placeholders = implode(', ', array_fill(0, \count($chunk), '(?, ?, ?, ?, ?, ?, ?, ?)'));
                $data = array_merge(...$chunk);

                $this->connection->executeQuery(
                    "INSERT INTO {$this->table} (`uuid`, `pid`, `path`, `hash`, `name`, `extension`, `type`, `tstamp`) VALUES $placeholders",
                    $data
                );
            }
        }

        // Updates
        foreach ($changeSet->getItemsToUpdate() as $pathIdentifier => $changedValues) {
            $dataToUpdate = [
                'tstamp' => $currentTime,
            ];

            if (null !== ($newPath = $changedValues[ChangeSet::ATTR_PATH] ?? null)) {
                $dataToUpdate['path'] = $this->convertToDatabasePath($newPath);
                $dataToUpdate['pid'] = $getParentUuid($pathIdentifier);
            }

            if (null !== ($newHash = $changedValues[ChangeSet::ATTR_HASH] ?? null)) {
                $dataToUpdate['hash'] = $newHash;
            }

            $this->connection->update(
                $this->table,
                $dataToUpdate,
                ['path' => $this->convertToDatabasePath($pathIdentifier)]
            );
        }

        // Deletes
        foreach ($changeSet->getItemsToDelete() as $pathToDelete) {
            $this->connection->delete(
                $this->table,
                ['path' => $this->convertToDatabasePath($pathToDelete)]
            );
        }

        $this->connection->commit();
    }

    /**
     * Loads paths from the database that should be considered when synchronizing.
     * his includes all parent directories and - in case of directories - all
     * resources that reside in it.
     *
     * This method also builds lookup tables for hashes and UUIDs of the entire
     * table.
     *
     * @param array<int, string> $searchPaths       non-empty list of search paths
     * @param array<int, string> $parentDirectories parent directories to consider
     *
     * @return array<int, array<int|string, string>>
     * @phpstan-return array{0: array<int, string>, 1: array<string, string>, 2: array<string, string>}
     */
    private function getDatabaseEntries(array $searchPaths, array $parentDirectories): array
    {
        $dbPaths = [];
        $allHashesByPath = [];
        $allUuidsByPath = [];

        $items = $this->connection->fetchAllNumeric(
            "SELECT path, uuid, hash, IF(type='folder', 1, 0) AS is_dir FROM {$this->table}"
        );

        $fullScope = '' === $searchPaths[0];

        foreach ($items as [$path, $uuid, $hash, $isDir]) {
            $path = $this->convertToFilesystemPath($path);

            // Include a path if it is either inside the search paths or is a
            // parent directory of it.
            $include = $fullScope || $this->inPath($path, $searchPaths) || ($isDir && \in_array($path, $parentDirectories, true));

            // Make directories distinguishable by appending a slash
            if ($isDir) {
                $path .= '/';
            }

            if ($include) {
                $dbPaths[] = $path;
            }

            $allHashesByPath[$path] = $hash;
            $allUuidsByPath[$path] = $uuid;
        }

        return [$dbPaths, $allHashesByPath, $allUuidsByPath];
    }

    /**
     * Traverses the filesystem and returns file and directory paths that can
     * be synchronized. For files, a hash of the content is returned alongside,
     * for directories null, for none-existing resources false.
     *
     * Items will always be listed before the directories they reside in (most
     * specific path first).
     *
     * @param array<int, string> $searchPaths       non-empty list of search paths
     * @param array<int, string> $parentDirectories parent directories to consider
     *
     * @return \Generator<string, (string|false|null)>
     */
    private function getFilesystemPaths(FilesystemAdapter $filesystem, array $searchPaths, array $parentDirectories): \Generator
    {
        $getHash = fn (string $path): string => hash($this->hashAlgorithm, $filesystem->read($path));

        $traverseRecursively = function (string $directory) use ($getHash, &$traverseRecursively, $filesystem): \Generator {
            if ($filesystem->fileExists(Path::join($directory, self::FILE_MARKER_EXCLUDED))) {
                return;
            }

            foreach ($filesystem->listContents($directory, false) as $entry) {
                $path = $entry->path();

                if (!$entry instanceof FileAttributes) {
                    yield from $traverseRecursively($path);

                    continue;
                }

                // Ignore file markers
                if (self::FILE_MARKER_PUBLIC === basename($path)) {
                    continue;
                }

                // Ignore files that are too big
                if ($entry->fileSize() > $this->maxFileSize) {
                    continue;
                }

                yield $path => $getHash($path);
            }

            // Only yield subdirectories
            if ('' !== $directory) {
                yield "$directory/" => null;
            }
        };

        $analyzeDirectory = static function (string $path) use ($filesystem): array {
            $paths = [];

            foreach ($filesystem->listContents($path, false) as $entry) {
                $paths[$entry->path()] = $entry->isDir();
            }

            return $paths;
        };

        // If a search path does not point to an existing file, we need to
        // determine if it's an existing directory or a non-existing resource.
        // Directories are _only_ listed reliably when calling `listContents`
        // on their parent directory (with deep=false). We keep track of
        // existing analyzed paths and store whether they are a directory.
        $analyzedPaths = [];

        foreach ($searchPaths as $searchPath) {
            if ('' === $searchPath) {
                yield from $traverseRecursively($searchPath);

                return;
            }

            if (null === ($isDir = $analyzedPaths[$searchPath] ?? null)) {
                if ($filesystem->fileExists($searchPath)) {
                    // Yield file
                    yield $searchPath => $getHash($searchPath);

                    continue;
                }

                // Analyze parent path
                $analyzedPaths = array_merge($analyzedPaths, $analyzeDirectory(Path::getDirectory($searchPath)));
                $isDir = $analyzedPaths[$searchPath] ??= false;
            }

            if ($isDir) {
                yield from $traverseRecursively($searchPath);

                continue;
            }

            // Yield none-existing (but requested) resource
            yield $searchPath => false;
        }

        foreach ($parentDirectories as $parentDirectory) {
            yield $parentDirectory.'/' => null;
        }
    }

    /**
     * Returns a normalized list of paths with redundant paths stripped as well
     * as a list of all parent paths that are not covered by the arguments.
     *
     * Example:
     *   foo/bar/baz/cat.jpg -> [foo/bar/baz/cat.jpg],
     *                                         [foo/bar/baz, foo/bar, foo]
     *
     *   foo/bar/baz/cat.jpg, foo -> [foo],
     *                                         []
     *
     *   foo/from/cat.jpg, foo/to/cat.jpg -> [foo/from/cat.jpg, foo/to/cat.jpg],
     *                                         [foo/to, foo/from, foo]
     *
     *   foo/bar, foo/bar/baz, other -> [foo/bar, other]
     *                                         [foo]
     *
     * @return array<int, array<int, string>>
     * @phpstan-return array{0: array<int, string>, 1: array<int, string>}
     */
    private function getNormalizedSearchPaths(string ...$paths): array
    {
        $paths = array_map(static fn (string $path): string => Path::canonicalize($path), $paths);

        if (0 === \count($paths) || \in_array('', $paths, true)) {
            return [[''], []];
        }

        // Make sure directories appear before their contents
        sort($paths);

        $searchPaths = [];
        $parentPaths = [];

        foreach ($paths as $path) {
            foreach ($searchPaths as $scope) {
                if (Path::isBasePath($scope, $path)) {
                    // Path if already covered
                    continue 2;
                }
            }

            $searchPaths[] = $path;
            $parentPath = $path;

            while ('.' !== ($parentPath = \dirname($parentPath))) {
                if (\in_array($parentPath, $parentPaths, true)) {
                    // Parent path is already covered
                    break;
                }

                $parentPaths[] = $parentPath;
            }
        }

        rsort($parentPaths);

        return [$searchPaths, $parentPaths];
    }

    /**
     * Returns true if a path is inside any of the given base paths.
     *
     * @param array<int, string> $basePaths
     */
    private function inPath(string $path, array $basePaths): bool
    {
        foreach ($basePaths as $basePath) {
            if (Path::isBasePath($basePath, $path)) {
                return true;
            }
        }

        return false;
    }

    private function convertToDatabasePath(string $filesystemPath): string
    {
        return Path::join($this->dbPathPrefix, $filesystemPath);
    }

    private function convertToFilesystemPath(string $databasePath): string
    {
        return Path::makeRelative($databasePath, $this->dbPathPrefix);
    }
}
