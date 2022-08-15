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

use Contao\CoreBundle\Filesystem\Dbafs\Hashing\Context;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGeneratorInterface;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @phpstan-type DatabasePaths array<string, self::RESOURCE_FILE|self::RESOURCE_DIRECTORY>
 * @phpstan-type FilesystemPaths \Generator<string, self::RESOURCE_*>
 * @phpstan-type Record array{isFile: bool, path: string, lastModified: ?int, fileSize: ?int, mimeType: ?string, extra: array<string, mixed>}
 *
 * @phpstan-import-type CreateItemDefinition from ChangeSet
 * @phpstan-import-type UpdateItemDefinition from ChangeSet
 * @phpstan-import-type DeleteItemDefinition from ChangeSet
 *
 * @experimental
 */
class Dbafs implements DbafsInterface, ResetInterface
{
    final public const FILE_MARKER_EXCLUDED = '.nosync';
    final public const FILE_MARKER_PUBLIC = '.public';

    private const RESOURCE_FILE = ChangeSet::TYPE_FILE;
    private const RESOURCE_DIRECTORY = ChangeSet::TYPE_DIRECTORY;
    private const RESOURCE_DOES_NOT_EXIST = -1;
    private const PATH_SUFFIX_SHALLOW_DIRECTORY = '//';

    private string $dbPathPrefix = '';
    private int $bulkInsertSize = 100;
    private bool $useLastModified = true;

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

    /**
     * @internal Use the "contao.filesystem.dbafs_factory" service to create new instances.
     */
    public function __construct(
        private HashGeneratorInterface $hashGenerator,
        private Connection $connection,
        private EventDispatcherInterface $eventDispatcher,
        private VirtualFilesystemInterface $filesystem,
        private string $table,
    ) {
    }

    public function setDatabasePathPrefix(string $prefix): void
    {
        $this->dbPathPrefix = Path::canonicalize($prefix);
    }

    public function setBulkInsertSize(int $chunkSize): void
    {
        $this->bulkInsertSize = $chunkSize;
    }

    public function useLastModified(bool $enable = true): void
    {
        $this->useLastModified = $enable;
    }

    public function getPathFromUuid(Uuid $uuid): string|null
    {
        $uuidBytes = $uuid->toBinary();

        if (!\array_key_exists($uuidBytes, $this->pathByUuid)) {
            $this->loadRecordByUuid($uuidBytes);
        }

        return $this->pathByUuid[$uuidBytes];
    }

    public function getPathFromId(int $id): string|null
    {
        if (!\array_key_exists($id, $this->pathById)) {
            $this->loadRecordById($id);
        }

        return $this->pathById[$id];
    }

    public function getRecord(string $path): FilesystemItem|null
    {
        if (!\array_key_exists($path, $this->records)) {
            $this->loadRecordByPath($path);
        }

        if (null !== ($record = $this->records[$path])) {
            return $this->toFilesystemItem($record);
        }

        return null;
    }

    public function getRecords(string $path, bool $deep = false): \Generator
    {
        $path = Path::join($this->dbPathPrefix, $path);
        $table = $this->connection->quoteIdentifier($this->table);

        if ($deep) {
            $rows = $this->connection->fetchAllAssociative(
                "SELECT * FROM $table WHERE path LIKE ? ORDER BY path",
                ["$path/%"]
            );
        } else {
            $rows = $this->connection->fetchAllAssociative(
                "SELECT * FROM $table WHERE path LIKE ? AND path NOT LIKE ? ORDER BY path",
                ["$path/%", "$path/%/%"]
            );
        }

        foreach ($rows as $row) {
            $itemPath = $this->convertToFilesystemPath($row['path']);

            if (!\array_key_exists($itemPath, $this->records)) {
                $this->populateRecord($row);
            }

            yield $this->toFilesystemItem($this->records[$itemPath]);
        }
    }

    public function setExtraMetadata(string $path, array $metadata): void
    {
        if (null === ($record = $this->getRecord($path))) {
            throw new \InvalidArgumentException(sprintf('Record for path "%s" does not exist.', $path));
        }

        if (!$record->isFile()) {
            throw new \LogicException(sprintf('Can only set extra metadata for files, directory given under "%s".', $path));
        }

        $row = [
            'uuid' => $uuid = array_flip($this->pathByUuid)[$path],
            'path' => $path,
        ];

        $columnFilter = array_flip($this->getExtraMetadataColumns());

        $event = new StoreDbafsMetadataEvent(
            $this->table,
            $row,
            // Remove non-matching columns before dispatching event
            array_intersect_key($metadata, $columnFilter)
        );

        $this->eventDispatcher->dispatch($event);

        $this->connection->update(
            $this->table,
            // Filter columns again before performing the query
            array_intersect_key($event->getRow(), $columnFilter),
            ['uuid' => $uuid]
        );
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

    public function sync(string ...$paths): ChangeSet
    {
        [$searchPaths, $parentPaths] = $this->getNormalizedSearchPaths(...$paths);

        // Gather all needed information from the database and filesystem
        [$dbPaths, $allDbHashesByPath, $allLastModifiedByPath, $allUuidsByPath] = $this->getDatabaseEntries($searchPaths, $parentPaths);
        $filesystemIterator = $this->getFilesystemPaths($searchPaths, $parentPaths);

        $changeSet = $this->doComputeChangeSet($dbPaths, $allDbHashesByPath, $allLastModifiedByPath, $filesystemIterator, $searchPaths);
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

        foreach (array_keys($changeSet->getItemsToDelete()) as $identifier) {
            unset($this->records[$identifier]);

            $this->pathById = array_diff($this->pathById, [$identifier]);
            $this->pathByUuid = array_diff($this->pathByUuid, [$identifier]);
        }

        return $changeSet;
    }

    /**
     * Computes the current change set. @See DbafsInterface::sync() for more
     * details on the $paths parameter.
     */
    public function computeChangeSet(string ...$paths): ChangeSet
    {
        [$searchPaths, $parentPaths] = $this->getNormalizedSearchPaths(...$paths);

        // Gather all needed information from the database and filesystem
        [$dbPaths, $allDbHashesByPath, $allLastModifiedByPath] = $this->getDatabaseEntries($searchPaths, $parentPaths);
        $filesystemIterator = $this->getFilesystemPaths($searchPaths, $parentPaths);

        return $this->doComputeChangeSet($dbPaths, $allDbHashesByPath, $allLastModifiedByPath, $filesystemIterator, $searchPaths);
    }

    public function getSupportedFeatures(): int
    {
        return $this->useLastModified ? DbafsInterface::FEATURE_LAST_MODIFIED : DbafsInterface::FEATURES_NONE;
    }

    /**
     * @phpstan-param Record $record
     */
    private function toFilesystemItem(array $record): FilesystemItem
    {
        return new FilesystemItem(
            $record['isFile'],
            $record['path'],
            isset($record['lastModified']) ? (int) ($record['lastModified']) : null,
            isset($record['fileSize']) ? (int) ($record['fileSize']) : null,
            $record['mimeType'] ?? null,
            $record['extra']
        );
    }

    /**
     * @param array<string, int>      $dbPaths
     * @param array<string, string>   $allDbHashesByPath
     * @param array<string, int|null> $allLastModifiedByPath
     * @param \Generator<string, int> $filesystemIterator
     * @param array<string>           $searchPaths
     *
     * @phpstan-param DatabasePaths   $dbPaths
     * @phpstan-param FilesystemPaths $filesystemIterator
     */
    private function doComputeChangeSet(array $dbPaths, array $allDbHashesByPath, array $allLastModifiedByPath, \Generator $filesystemIterator, array $searchPaths): ChangeSet
    {
        // We're identifying items by their (old) path and store any detected
        // changes as an array of definitions
        /** @phpstan-var array<string, CreateItemDefinition> $itemsToCreate */
        $itemsToCreate = [];

        /** @phpstan-var array<string, UpdateItemDefinition> $itemsToUpdate */
        $itemsToUpdate = [];

        // To detect orphans, we start with a list of all items and remove them
        // once found
        /** @phpstan-var array<string, DeleteItemDefinition> $itemsToDelete */
        $itemsToDelete = $dbPaths;

        // We keep a list of hashes and names of traversed child elements
        // indexed by their directory path, so that we are later able to
        // compute the directory hash
        /** @var array<string, array<string>> $dirHashesParts */
        $dirHashesParts = [];

        /** @var array<string, int|null> $lastModifiedUpdates */
        $lastModifiedUpdates = [];

        $isPartialSync = \count($dbPaths) !== \count($allDbHashesByPath);

        foreach ($filesystemIterator as $path => $type) {
            $name = basename($path);
            $parentDir = \dirname($path);
            $oldHash = $allDbHashesByPath[$path] ?? null;

            if (self::RESOURCE_FILE === $type) {
                $oldLastModified = $allLastModifiedByPath[$path] ?? null;

                // Allow falling back (= skip hashing) to the existing hash if
                // useLastModified is enabled, and we already got an existing
                // timestamp
                $fallback = $this->useLastModified && null !== $oldLastModified ? $oldHash : null;

                $hashContext = new Context($fallback, $oldLastModified);
                $this->hashGenerator->hashFileContent($this->filesystem, $path, $hashContext);

                if ($this->useLastModified && $hashContext->lastModifiedChanged()) {
                    $lastModifiedUpdates[$path] = $hashContext->getLastModified();
                }

                $hash = $hashContext->getResult();
            } elseif (self::RESOURCE_DIRECTORY === $type) {
                $childHashes = $dirHashesParts[$path] ?? [];

                // In partial sync we need to manually add child hashes of
                // items that we do not traverse but which still contribute to
                // the directory hash
                if ($isPartialSync && !$this->inPath($path, $searchPaths, false)) {
                    $directChildrenPattern = sprintf('@^%s/[^/]+[/]?$@', preg_quote($path, '@'));

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
                $hash = $this->hashGenerator->hashString(implode("\0", $childHashes));

                unset($dirHashesParts[$path]);
            } else {
                // Remember hash and name; skip non-existent resources,
                $dirHashesParts[$parentDir][$name] = null;

                continue;
            }

            $dirHashesParts[$parentDir][$name] = $hash.$name;

            // Detect changes
            if (null === $oldHash) {
                // Resource was not found; create a new record (we're detecting moves further down)
                $itemsToCreate[$path] = [ChangeSet::ATTR_HASH => $hash, ChangeSet::ATTR_PATH => $path, ChangeSet::ATTR_TYPE => $type];
            } elseif ($hash !== $oldHash) {
                if ($dbPaths[$path] !== $type) {
                    // Type has changed; create a new record and delete the current one
                    $itemsToCreate[$path] = [ChangeSet::ATTR_HASH => $hash, ChangeSet::ATTR_PATH => $path, ChangeSet::ATTR_TYPE => $type];

                    continue;
                }

                // Hash has changed; update the record
                $itemsToUpdate[$path] = [ChangeSet::ATTR_HASH => $hash];
            }

            unset($itemsToDelete[$path]);
        }

        // Ignore all children of shallow directories
        $shallowDirectories = array_filter(
            $searchPaths,
            static fn (string $path): bool => self::PATH_SUFFIX_SHALLOW_DIRECTORY === substr($path, -2)
        );

        if (!empty($shallowDirectories)) {
            foreach (array_keys($itemsToDelete) as $item) {
                if ($this->inPath($item, $shallowDirectories)) {
                    unset($itemsToDelete[$item]);
                }
            }
        }

        // Detect moves: If items that should get created can be found in the
        // list of orphans, only update their path.
        $hasMoves = false;

        foreach ($itemsToCreate as $path => $dataToInsert) {
            $candidates = array_intersect(
                array_keys($itemsToDelete),
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

            if (null !== ($lastModified = $lastModifiedUpdates[$path] ?? null)) {
                $lastModifiedUpdates[$oldPath] = $lastModified;
                unset($lastModifiedUpdates[$path]);
            }

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
            $itemsToDelete,
            $lastModifiedUpdates
        );
    }

    private function loadRecordByUuid(string $uuid): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT * FROM %s WHERE uuid=?', $this->connection->quoteIdentifier($this->table)),
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
            sprintf('SELECT * FROM %s WHERE id=?', $this->connection->quoteIdentifier($this->table)),
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
            sprintf('SELECT * FROM %s WHERE path=?', $this->connection->quoteIdentifier($this->table)),
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

        $event = new RetrieveDbafsMetadataEvent($this->table, $row);
        $this->eventDispatcher->dispatch($event);

        /** @phpstan-var Record $record */
        $record = [
            'isFile' => $isFile,
            'path' => $path,
            'extra' => $event->getExtraMetadata(),
        ];

        if ($this->useLastModified) {
            $record['lastModified'] = $row['lastModified'];
        }

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
        if ($changeSet->isEmpty($this->useLastModified)) {
            return;
        }

        $getParentUuid = static function (string $path) use (&$allUuidsByPath): ?string {
            if ('.' === ($parentPath = \dirname($path))) {
                return null;
            }

            return $allUuidsByPath[$parentPath];
        };

        $allLastModifiedUpdatesByPath = $changeSet->getLastModifiedUpdates();

        $this->connection->beginTransaction();

        // Inserts
        $currentTime = time();
        $inserts = [];

        foreach ($changeSet->getItemsToCreate() as $newValues) {
            $newUuid = Uuid::v1()->toBinary();
            $newPath = $newValues[ChangeSet::ATTR_PATH];
            $isDir = ChangeSet::TYPE_DIRECTORY === $newValues[ChangeSet::ATTR_TYPE];

            if ($isDir) {
                // Add new UUID to lookup, so that child entries will be able to reference it
                $allUuidsByPath[$newPath] = $newUuid;
            }

            $dataToInsert = [
                'uuid' => $newUuid,
                'pid' => $getParentUuid($newPath),
                'path' => $this->convertToDatabasePath($newPath),
                'hash' => $newValues[ChangeSet::ATTR_HASH],
                'type' => $isDir ? 'folder' : 'file',
            ];

            // Backwards compatibility
            if ('tl_files' === $this->table) {
                $dataToInsert['name'] = basename($newPath);
                $dataToInsert['extension'] = !$isDir ? Path::getExtension($newPath) : '';
                $dataToInsert['tstamp'] = $currentTime;
            }

            if ($this->useLastModified) {
                $dataToInsert['lastModified'] = $allLastModifiedUpdatesByPath[$newPath] ?? null;
            }

            $inserts[] = $dataToInsert;
        }

        if (!empty($inserts)) {
            $table = $this->connection->quoteIdentifier($this->table);
            $columns = sprintf('`%s`', implode('`, `', array_keys($inserts[0]))); // "uuid", "pid", …
            $placeholders = sprintf('(%s)', implode(', ', array_fill(0, \count($inserts[0]), '?'))); // (?, ?, …, ?)

            foreach (array_chunk($inserts, $this->bulkInsertSize) as $chunk) {
                $this->connection->executeQuery(
                    sprintf(
                        'INSERT INTO %s (%s) VALUES %s',
                        $table,
                        $columns,
                        implode(', ', array_fill(0, \count($chunk), $placeholders))
                    ),
                    array_merge(...array_map('array_values', $chunk))
                );
            }
        }

        // Updates
        foreach ($changeSet->getItemsToUpdate($this->useLastModified) as $pathIdentifier => $changedValues) {
            $dataToUpdate = [
                'tstamp' => $currentTime,
            ];

            if (null !== ($newPath = $changedValues[ChangeSet::ATTR_PATH] ?? null)) {
                $dataToUpdate['path'] = $this->convertToDatabasePath($newPath);
                $dataToUpdate['pid'] = $getParentUuid($newPath);
            }

            if (null !== ($newHash = $changedValues[ChangeSet::ATTR_HASH] ?? null)) {
                $dataToUpdate['hash'] = $newHash;
            }

            if (false !== ($newLastModified = $changedValues[ChangeSet::ATTR_LAST_MODIFIED] ?? false)) {
                $dataToUpdate['lastModified'] = $newLastModified;
            }

            $this->connection->update(
                $this->table,
                $dataToUpdate,
                ['path' => $this->convertToDatabasePath($pathIdentifier)]
            );
        }

        // Deletes
        foreach ($changeSet->getItemsToDelete() as $pathToDelete => $type) {
            $this->connection->delete(
                $this->table,
                [
                    'path' => $this->convertToDatabasePath($pathToDelete),
                    'type' => ChangeSet::TYPE_FILE === $type ? 'file' : 'folder',
                ]
            );
        }

        $this->connection->commit();
    }

    /**
     * Loads paths from the database that should be considered when synchronizing.
     *
     * This includes all parent directories and - in case of directories - all
     * resources that reside in it.
     *
     * This method also builds lookup tables for hashes, "last modified" timestamps
     * and UUIDs of the entire table.
     *
     * @param array<string> $searchPaths       non-empty list of search paths
     * @param array<string> $parentDirectories parent directories to consider
     *
     * @return array<array<string, string|int|null>>
     *
     * @phpstan-return array{0: DatabasePaths, 1: array<string, string>, 2: array<string, int|null>, 3: array<string, string>}
     */
    private function getDatabaseEntries(array $searchPaths, array $parentDirectories): array
    {
        $dbPaths = [];
        $allHashesByPath = [];
        $allLastModifiedByPath = [];
        $allUuidsByPath = [];

        $items = $this->connection->fetchAllNumeric(
            sprintf(
                "SELECT path, uuid, hash, IF(type='folder', 1, 0), %s FROM %s",
                $this->useLastModified ? 'lastModified' : 'NULL',
                $this->connection->quoteIdentifier($this->table),
            )
        );

        $fullScope = '' === $searchPaths[0];

        foreach ($items as [$path, $uuid, $hash, $isDir, $lastModified]) {
            $path = $this->convertToFilesystemPath($path);

            // Include a path if it is either inside the search paths or is a
            // parent directory of it.
            if ($fullScope || $this->inPath($path, $searchPaths) || ($isDir && \in_array($path, $parentDirectories, true))) {
                $dbPaths[$path] = $isDir ? self::RESOURCE_DIRECTORY : self::RESOURCE_FILE;
            }

            $allHashesByPath[$path] = $hash;
            $allLastModifiedByPath[$path] = (int) $lastModified;
            $allUuidsByPath[$path] = $uuid;
        }

        return [$dbPaths, $allHashesByPath, $allLastModifiedByPath, $allUuidsByPath];
    }

    /**
     * Traverses the filesystem and returns file and directory paths that can
     * be synchronized.
     *
     * Items will always be listed before the directories they reside in (most
     * specific path first).
     *
     * @param array<string> $searchPaths       Non-empty list of search paths
     * @param array<string> $parentDirectories Parent directories to consider
     *
     * @return \Generator<string, int>
     *
     * @phpstan-return FilesystemPaths
     */
    private function getFilesystemPaths(array $searchPaths, array $parentDirectories): \Generator
    {
        $traverseRecursively = function (string $directory, bool $shallow = false) use (&$traverseRecursively): \Generator {
            if ($this->filesystem->fileExists(Path::join($directory, self::FILE_MARKER_EXCLUDED), VirtualFilesystemInterface::BYPASS_DBAFS)) {
                return;
            }

            /** @var FilesystemItem $item */
            foreach ($this->filesystem->listContents($directory, false, VirtualFilesystemInterface::BYPASS_DBAFS) as $item) {
                $path = $item->getPath();

                if (!$item->isFile()) {
                    if (!$shallow) {
                        yield from $traverseRecursively($path);
                    }

                    continue;
                }

                // Ignore dot files
                if (str_starts_with(basename($path), '.')) {
                    continue;
                }

                yield $path => self::RESOURCE_FILE;
            }

            // Only yield subdirectories
            if ('' !== $directory) {
                yield $directory => self::RESOURCE_DIRECTORY;
            }
        };

        $analyzeDirectory = function (string $path): array {
            $paths = [];

            /** @var FilesystemItem $entry */
            foreach ($this->filesystem->listContents($path, false, VirtualFilesystemInterface::BYPASS_DBAFS) as $entry) {
                $paths[$entry->getPath()] = !$entry->isFile();
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

            $shallow = false;

            if (self::PATH_SUFFIX_SHALLOW_DIRECTORY === substr($searchPath, -2)) {
                $searchPath = rtrim($searchPath, '/');
                $shallow = true;
            }

            if (null === ($isDir = $analyzedPaths[$searchPath] ?? null)) {
                if (!$shallow && $this->filesystem->fileExists($searchPath, VirtualFilesystemInterface::BYPASS_DBAFS)) {
                    // Yield file
                    yield $searchPath => self::RESOURCE_FILE;

                    continue;
                }

                // Analyze parent path
                $analyzedPaths = [...$analyzedPaths, ...$analyzeDirectory(Path::getDirectory($searchPath))];
                $isDir = $analyzedPaths[$searchPath] ??= false;
            }

            if ($isDir) {
                yield from $traverseRecursively($searchPath, $shallow);

                continue;
            }

            // Yield non-existing (but requested) resource
            yield $searchPath => self::RESOURCE_DOES_NOT_EXIST;
        }

        foreach ($parentDirectories as $parentDirectory) {
            yield $parentDirectory => self::RESOURCE_DIRECTORY;
        }
    }

    /**
     * Returns true if a path is inside any of the given base paths.
     *
     * All provided paths are expected to be normalized and may contain a
     * double slash (//) as suffix.
     *
     * If $considerShallowDirectories is set to false, paths that are directly
     * inside shallow directories (e.g. "foo/bar" in "foo") do NOT yield a
     * truthy result.
     *
     * @param array<string> $basePaths
     */
    private function inPath(string $path, array $basePaths, bool $considerShallowDirectories = true): bool
    {
        foreach ($basePaths as $basePath) {
            // Any sub path
            if (str_starts_with($path.'/', $basePath.'/')) {
                return true;
            }

            if (!$considerShallowDirectories) {
                continue;
            }

            // Direct child of shallow directory
            $basePath = preg_quote(rtrim($basePath, '/'), '@');

            if ($path === $basePath || 1 === preg_match("@^$basePath/[^/]*(//)?$@", $path)) {
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

    /**
     * Returns a normalized list of paths with redundant paths stripped as well
     * as a list of all parent paths that are not covered by the arguments. To
     * denote directories of which only the direct children should be read, we
     * append a double slash (//) as an internal marker.
     *
     * @see \Contao\CoreBundle\Tests\Filesystem\DbafsTest::testNormalizesSearchPaths()
     *
     * @return array{0: array<string>, 1: array<string>}
     */
    private function getNormalizedSearchPaths(string ...$paths): array
    {
        $paths = array_map(
            static function (string $path): string {
                $path = trim(Path::canonicalize($path));

                if (Path::isAbsolute($path)) {
                    throw new \InvalidArgumentException(sprintf('Absolute path "%s" is not allowed when synchronizing.', $path));
                }

                if (str_starts_with($path, '.')) {
                    throw new \InvalidArgumentException(sprintf('Dot path "%s" is not allowed when synchronizing.', $path));
                }

                return $path;
            },
            $paths
        );

        if (0 === \count($paths) || \in_array('', $paths, true)) {
            return [[''], []];
        }

        // Make sure directories appear before their contents
        sort($paths);

        $shallowDirectories = [];
        $deepDirectories = [];

        // Normalize "/**" and "/*" suffixes
        $paths = array_map(
            static function (string $path) use (&$shallowDirectories, &$deepDirectories): string {
                if (preg_match('@^[^*]+/(\*\*?)@', $path, $matches)) {
                    $path = rtrim($path, '/*');

                    if ('*' === $matches[1]) {
                        $shallowDirectories[] = $path;
                    } else {
                        $deepDirectories[] = $path;
                    }
                }

                return $path;
            },
            $paths
        );

        $shallowDirectories = array_diff($shallowDirectories, $deepDirectories);

        $searchPaths = [];
        $parentPaths = [];

        foreach ($paths as $path) {
            foreach (array_diff($searchPaths, $shallowDirectories) as $scope) {
                if (Path::isBasePath($scope, $path)) {
                    // Path if already covered
                    continue 2;
                }
            }

            $searchPaths[] = $path;
            $parentPath = $path;

            while ('.' !== ($parentPath = \dirname($parentPath))) {
                if (\in_array($parentPath, $parentPaths, true) || \in_array($parentPath, $shallowDirectories, true)) {
                    // Parent path is already covered
                    break;
                }

                $parentPaths[] = $parentPath;
            }
        }

        // Append marker for shallow directories
        foreach ($shallowDirectories as $directory) {
            $searchPaths[array_search($directory, $searchPaths, true)] = $directory.self::PATH_SUFFIX_SHALLOW_DIRECTORY;
        }

        rsort($parentPaths);

        return [$searchPaths, $parentPaths];
    }

    private function getExtraMetadataColumns(): array
    {
        $columns = array_map(
            static fn (Column $column): string => $column->getName(),
            $this->connection->createSchemaManager()->listTableColumns($this->table)
        );

        $defaultFields = [
            'id', 'pid', 'uuid', 'path',
            'hash', 'lastModified', 'type',
            'extension', 'found', 'name', 'tstamp',
        ];

        return array_diff($columns, $defaultFields);
    }
}
