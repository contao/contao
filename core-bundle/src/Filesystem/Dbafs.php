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

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\RetrieveDbafsMetadataEvent;
use Contao\CoreBundle\Event\StoreDbafsMetadataEvent;
use Doctrine\DBAL\Connection;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @phpstan-type DatabasePaths array<string, self::RESOURCE_FILE|self::RESOURCE_DIRECTORY>
 * @phpstan-type FilesystemPaths \Generator<string, self::RESOURCE_*>
 *
 * @phpstan-import-type CreateItemDefinition from ChangeSet
 * @phpstan-import-type UpdateItemDefinition from ChangeSet
 * @phpstan-import-type DeleteItemDefinition from ChangeSet
 *
 * @phpstan-import-type Record from DbafsInterface
 * @phpstan-import-type ExtraMetadata from DbafsInterface
 */
class Dbafs implements ResetInterface, DbafsInterface
{
    public const FILE_MARKER_EXCLUDED = '.nosync';
    public const FILE_MARKER_PUBLIC = '.public';

    private const RESOURCE_FILE = ChangeSet::TYPE_FILE;
    private const RESOURCE_DIRECTORY = ChangeSet::TYPE_FOLDER;
    private const RESOURCE_DOES_NOT_EXIST = -1;
    private const PATH_SUFFIX_SHALLOW_DIRECTORY = '//';

    private HashGeneratorInterface $hashGenerator;
    private Connection $connection;
    private EventDispatcherInterface $eventDispatcher;

    private string $table;
    private string $dbPathPrefix = '';
    private int $maxFileSize = 2147483648; // 2 GiB
    private int $bulkInsertSize = 100;
    private bool $useLastModified = false;

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

    public function __construct(HashGeneratorInterface $hashGenerator, Connection $connection, EventDispatcherInterface $eventDispatcher, string $table)
    {
        $this->hashGenerator = $hashGenerator;
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;

        $this->table = $table;
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

    public function useLastModified(bool $enable): void
    {
        $this->useLastModified = $enable;
    }

    public function getPathFromUuid(Uuid $uuid): ?string
    {
        $uuidBytes = $uuid->toBinary();

        if (!\array_key_exists($uuidBytes, $this->pathByUuid)) {
            $this->loadRecordByUuid($uuidBytes);
        }

        return $this->pathByUuid[$uuidBytes];
    }

    public function getPathFromId(int $id): ?string
    {
        if (!\array_key_exists($id, $this->pathById)) {
            $this->loadRecordById($id);
        }

        return $this->pathById[$id];
    }

    public function getRecord(string $path): ?array
    {
        if (!\array_key_exists($path, $this->records)) {
            $this->loadRecordByPath($path);
        }

        return $this->records[$path];
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

            yield $this->records[$itemPath];
        }
    }

    public function setExtraMetadata(string $path, array $metadata): void
    {
        if (null === ($record = $this->getRecord($path))) {
            throw new \InvalidArgumentException("Record for path $path does not exist.");
        }

        if (!$record['isFile']) {
            throw new \InvalidArgumentException("Can only set extra metadata for files, directory given under $path.");
        }

        $row = [
            'uuid' => $uuid = $this->pathByUuid[$path],
            'path' => $path,
        ];

        $event = new StoreDbafsMetadataEvent($this->table, $row, $metadata);
        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::STORE_DBAFS_METADATA);

        $this->connection->update(
            $this->table,
            $event->getRow(),
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

    public function sync(FilesystemAdapter $filesystem, string ...$scope): ChangeSet
    {
        [$searchPaths, $parentPaths] = $this->getNormalizedSearchPaths(...$scope);

        // Gather all needed information from the database and filesystem
        [$dbPaths, $allDbHashesByPath, $allLastModifiedByPath, $allUuidsByPath] = $this->getDatabaseEntries($searchPaths, $parentPaths);
        $filesystemIterator = $this->getFilesystemPaths($filesystem, $searchPaths, $parentPaths);

        $changeSet = $this->doComputeChangeSet($dbPaths, $allDbHashesByPath, $allLastModifiedByPath, $filesystemIterator, $filesystem, $searchPaths);
        $this->applyChangeSet($changeSet, $allUuidsByPath, $allLastModifiedByPath);

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
     * Computes the change set between the database and a given filesystem
     * adapter. @See DbafsInterface::sync() for more details on the $scope
     * parameter.
     */
    public function computeChangeSet(FilesystemAdapter $filesystem, string ...$scope): ChangeSet
    {
        [$searchPaths, $parentPaths] = $this->getNormalizedSearchPaths(...$scope);

        // Gather all needed information from the database and filesystem
        [$dbPaths, $allDbHashesByPath, $allLastModifiedByPath] = $this->getDatabaseEntries($searchPaths, $parentPaths);
        $filesystemIterator = $this->getFilesystemPaths($filesystem, $searchPaths, $parentPaths);

        return $this->doComputeChangeSet($dbPaths, $allDbHashesByPath, $allLastModifiedByPath, $filesystemIterator, $filesystem, $searchPaths);
    }

    public function supportsLastModified(): bool
    {
        return $this->useLastModified;
    }

    public function supportsFileSize(): bool
    {
        return false;
    }

    public function supportsMimeType(): bool
    {
        return false;
    }

    /**
     * @param array<string, int>      $dbPaths
     * @param array<string, string>   $allDbHashesByPath
     * @param array<string, int|null> $allLastModifiedByPath
     * @param \Generator<string, int> $filesystemIterator
     * @param array<string>           $searchPaths
     *
     * @phpstan-param DatabasePaths $dbPaths
     * @phpstan-param FilesystemPaths $filesystemIterator
     */
    private function doComputeChangeSet(array $dbPaths, array $allDbHashesByPath, array &$allLastModifiedByPath, \Generator $filesystemIterator, FilesystemAdapter $filesystem, array $searchPaths): ChangeSet
    {
        // We're identifying items by their (old) path and store any detected
        // changes as an array of attributes
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

        $isPartialSync = \count($dbPaths) !== \count($allDbHashesByPath);

        $getFileContentHash = function (string $path) use ($filesystem, $allDbHashesByPath, &$allLastModifiedByPath): string {
            if ($this->useLastModified) {
                $oldHash = $allDbHashesByPath[$path] ?? null;
                $lastModified = null !== $oldHash ? ($allLastModifiedByPath[$path] ?? null) : null;

                // Allow falling back to the existing hash if we've already got
                // an existing hash and timestamp
                $hash = $this->hashGenerator->hashFileContent($filesystem, $path, $lastModified, $fileLastModified) ?? $oldHash;

                // Update last modified reference
                $allLastModifiedByPath[$path] = $fileLastModified ?? $filesystem->lastModified($path)->lastModified();

                return $hash;
            }

            $hash = $this->hashGenerator->hashFileContent($filesystem, $path);

            if (null === $hash) {
                throw new \LogicException('A hash generator may not return null if $lastModified was not set.');
            }

            return $hash;
        };

        foreach ($filesystemIterator as $path => $type) {
            $name = basename($path);
            $parentDir = \dirname($path);

            if (self::RESOURCE_FILE === $type) {
                $hash = $getFileContentHash($path);
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
            $hash2 = $allDbHashesByPath[$path] ?? null;

            if (null === $hash2) {
                // Resource was not found; create a new record (we're detecting moves further down)
                $itemsToCreate[$path] = [ChangeSet::ATTR_HASH => $hash, ChangeSet::ATTR_PATH => $path, ChangeSet::ATTR_TYPE => $type];
            } elseif ($hash !== $hash2) {
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
            $itemsToDelete
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
        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::RETRIEVE_DBAFS_METADATA);

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
     * @param array<string, string>   $allUuidsByPath
     * @param array<string, int|null> $allLastModifiedByPath
     */
    private function applyChangeSet(ChangeSet $changeSet, array $allUuidsByPath, array $allLastModifiedByPath): void
    {
        if ($changeSet->isEmpty()) {
            return;
        }

        $getParentUuid = static function (string $path) use (&$allUuidsByPath): ?string {
            if ('.' === ($parentPath = \dirname($path))) {
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
            $newUuid = Uuid::v1()->toBinary();
            $newPath = $newValues[ChangeSet::ATTR_PATH];
            $isDir = ChangeSet::TYPE_FOLDER === $newValues[ChangeSet::ATTR_TYPE];

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

            // BC
            if ('tl_files' === $this->table) {
                $dataToInsert['name'] = basename($newPath);
                $dataToInsert['extension'] = !$isDir ? Path::getExtension($newPath) : '';
                $dataToInsert['tstamp'] = $currentTime;
            }

            if ($this->useLastModified) {
                $dataToInsert['lastModified'] = $allLastModifiedByPath[$newPath] ?? null;
            }

            $inserts[] = $dataToInsert;
        }

        if (!empty($inserts)) {
            $table = $this->connection->quoteIdentifier($this->table);
            $columns = sprintf('`%s`', implode('`, `', array_keys($inserts[0]))); // `uuid`, `pid`, …
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

            if ($this->useLastModified && null !== ($lastModified = $allLastModifiedByPath[$pathIdentifier] ?? null)) {
                $dataToUpdate['lastModified'] = $lastModified;
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
     * his includes all parent directories and - in case of directories - all
     * resources that reside in it.
     *
     * This method also builds lookup tables for hashes, 'last modified' timestamps
     * and UUIDs of the entire table.
     *
     * @param array<string> $searchPaths       non-empty list of search paths
     * @param array<string> $parentDirectories parent directories to consider
     *
     * @return array<array<string, string|int|null>>
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
     * @param array<string> $searchPaths       non-empty list of search paths
     * @param array<string> $parentDirectories parent directories to consider
     *
     * @return \Generator<string, int>
     * @phpstan-return FilesystemPaths
     */
    private function getFilesystemPaths(FilesystemAdapter $filesystem, array $searchPaths, array $parentDirectories): \Generator
    {
        $traverseRecursively = function (string $directory, bool $shallow = false) use (&$traverseRecursively, $filesystem): \Generator {
            if ($filesystem->fileExists(Path::join($directory, self::FILE_MARKER_EXCLUDED))) {
                return;
            }

            foreach ($filesystem->listContents($directory, false) as $entry) {
                $path = $entry->path();

                if (!$entry instanceof FileAttributes) {
                    if (!$shallow) {
                        yield from $traverseRecursively($path);
                    }

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

                yield $path => self::RESOURCE_FILE;
            }

            // Only yield subdirectories
            if ('' !== $directory) {
                yield $directory => self::RESOURCE_DIRECTORY;
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

            $shallow = false;

            if (self::PATH_SUFFIX_SHALLOW_DIRECTORY === substr($searchPath, -2)) {
                $searchPath = rtrim($searchPath, '/');
                $shallow = true;
            }

            if (null === ($isDir = $analyzedPaths[$searchPath] ?? null)) {
                if (!$shallow && $filesystem->fileExists($searchPath)) {
                    // Yield file
                    yield $searchPath => self::RESOURCE_FILE;

                    continue;
                }

                // Analyze parent path
                $analyzedPaths = array_merge($analyzedPaths, $analyzeDirectory(Path::getDirectory($searchPath)));
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
     * Returns a normalized list of paths with redundant paths stripped as well
     * as a list of all parent paths that are not covered by the arguments. To
     * denote directories of which only the direct children should be read, we
     * append a double slash (//) as an internal marker.
     *
     * @see \Contao\CoreBundle\Tests\Filesystem\DbafsTest::testNormalizesSearchPaths()
     * for examples.
     *
     * @return array<array<string>>
     * @phpstan-return array{0: array<string>, 1: array<string>}
     */
    private function getNormalizedSearchPaths(string ...$paths): array
    {
        $paths = array_map(
            static function (string $path): string {
                $path = trim(Path::canonicalize($path));

                if (Path::isAbsolute($path)) {
                    throw new \InvalidArgumentException("Absolute path '$path' is not allowed when synchronizing.");
                }

                if (0 === strpos($path, '.')) {
                    throw new \InvalidArgumentException("Dot path '$path' is not allowed when synchronizing.");
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

        // Normalize '/**' and '/*' suffixes
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

    /**
     * Returns true if a path is inside any of the given base paths. All
     * provided paths are expected to be normalized and may contain a double
     * slash (//) as suffix.
     *
     * If $considerShallowDirectories is set to false, paths that are directly
     * inside shallow directories (e.g. 'foo/bar' in 'foo') do NOT yield a
     * truthy result.
     *
     * @param array<string> $basePaths
     */
    private function inPath(string $path, array $basePaths, bool $considerShallowDirectories = true): bool
    {
        foreach ($basePaths as $basePath) {
            // Any sub path
            if (0 === mb_strpos($path.'/', $basePath.'/')) {
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
}
