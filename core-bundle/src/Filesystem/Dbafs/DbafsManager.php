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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

/**
 * This class manages access to multiple DBAFS instances. Each instance can be
 * registered with a prefix path and will be queried accordingly.
 *
 * Note: In general, user code should not directly interface with the
 *       DbafsManager, but use the VirtualFilesystem instead.
 *
 * @see VirtualFilesystem
 *
 * @experimental
 */
class DbafsManager
{
    /**
     * @var array<string|int, DbafsInterface>
     */
    private array $dbafs = [];

    public function register(DbafsInterface $dbafs, string $pathPrefix): void
    {
        $this->dbafs[$pathPrefix] = $dbafs;

        krsort($this->dbafs, SORT_NATURAL);

        if (\count($this->dbafs) > 1) {
            $this->validateTransitiveProperties();
        }
    }

    /**
     * Returns true if a DBAFS is registered that can serve this path.
     */
    public function match(string $path): bool
    {
        return null !== $this->getDbafsForPath($path)->current();
    }

    /**
     * Returns true if a resource exists under this path.
     */
    public function has(string $path): bool
    {
        return null !== $this->getRecord($path);
    }

    /**
     * Returns true if a file exists under this path.
     */
    public function fileExists(string $path): bool
    {
        return ($record = $this->getRecord($path)) && $record->isFile();
    }

    /**
     * Returns true if a directory exists under this path.
     */
    public function directoryExists(string $path): bool
    {
        return ($record = $this->getRecord($path)) && !$record->isFile();
    }

    /**
     * Resolves a UUID to a path.
     *
     * All registered DBAFS are queried until the request can be fulfilled, otherwise
     * an UnableToResolveUuidException will be thrown. You can constrain querying only
     * a subset by providing a path $prefix.
     *
     * The returned path will always be relative to the provided prefix:
     *
     *     resolveUuid($uuid); // returns "files/foo/bar"
     *     resolveUuid($uuid, 'files/foo'); // returns "bar"
     *
     * @throws UnableToResolveUuidException
     */
    public function resolveUuid(Uuid $uuid, string $prefix = ''): string
    {
        foreach ($this->getCandidatesForPrefix($prefix) as $dbafsPrefix => $dbafs) {
            if (null !== ($path = $dbafs->getPathFromUuid($uuid))) {
                return Path::makeRelative(Path::join($dbafsPrefix, $path), $prefix);
            }
        }

        throw new UnableToResolveUuidException($uuid);
    }

    /**
     * Returns the last modified time or null if no DBAFS exists for the given $path
     * that supports the attribute and contains a matching record.
     */
    public function getLastModified(string $path): int|null
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        if (
            ($dbafs = $dbafsIterator->current())
            && $dbafs->getSupportedFeatures() & DbafsInterface::FEATURE_LAST_MODIFIED
            && ($record = $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key())))
        ) {
            return $record->getLastModified();
        }

        return null;
    }

    /**
     * Returns the file size or null if no DBAFS exists for the given $path that
     * supports the attribute and contains a matching record.
     */
    public function getFileSize(string $path): int|null
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        if (
            ($dbafs = $dbafsIterator->current())
            && $dbafs->getSupportedFeatures() & DbafsInterface::FEATURE_FILE_SIZE
            && ($record = $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key())))
        ) {
            return $record->getFileSize();
        }

        return null;
    }

    /**
     * Returns the mime type or null if no DBAFS exists for the given $path that
     * supports the attribute and contains a matching record.
     */
    public function getMimeType(string $path): string|null
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        if (
            ($dbafs = $dbafsIterator->current())
            && $dbafs->getSupportedFeatures() & DbafsInterface::FEATURE_MIME_TYPE
            && ($record = $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key())))
        ) {
            return $record->getMimeType();
        }

        return null;
    }

    /**
     * Returns merged extra metadata from all DBAFS that are able to serve the given $path.
     */
    public function getExtraMetadata(string $path): ExtraMetadata
    {
        $metadataChunks = [];
        $metadataKeys = [];

        foreach ($this->getDbafsForPath($path) as $prefix => $dbafs) {
            if ($record = $dbafs->getRecord(Path::makeRelative($path, $prefix))) {
                $chunk = $record->getExtraMetadata()->all();
                $keys = array_keys($chunk);

                if ($duplicates = array_intersect($metadataKeys, $keys)) {
                    throw new \LogicException(\sprintf('The metadata key(s) "%s" appeared in more than one matching DBAFS for path "%s".', implode('", "', $duplicates), $path));
                }

                $metadataChunks[] = $chunk;
                $metadataKeys = [...$metadataKeys, ...$keys];
            }
        }

        return new ExtraMetadata(array_merge(...array_reverse($metadataChunks)));
    }

    /**
     * Sets extra metadata to all DBAFS that are able to serve the given $path.
     */
    public function setExtraMetadata(string $path, ExtraMetadata $metadata): void
    {
        $success = false;

        foreach ($this->getDbafsForPath($path) as $prefix => $dbafs) {
            $resolvedPath = Path::makeRelative($path, $prefix);

            try {
                $dbafs->setExtraMetadata($resolvedPath, $metadata);
                $success = true;
            } catch (\InvalidArgumentException) {
                // ignore
            }
        }

        if (!$success) {
            throw new \InvalidArgumentException(\sprintf('No resource exists for the given path "%s".', $path));
        }
    }

    /**
     * List contents from all DBAFS that are able to serve the given $path.
     *
     * Each path is guaranteed to be only reported once, i.e. identical paths from
     * DBAFs with a lower specificity will be ignored.
     *
     * @return \Generator<FilesystemItem>
     */
    public function listContents(string $path, bool $deep = false): \Generator
    {
        $covered = [];

        foreach ($this->getDbafsForPath($path) as $prefix => $dbafs) {
            foreach ($dbafs->getRecords(Path::makeRelative($path, $prefix), $deep) as $item) {
                $itemPath = Path::join($prefix, $item->getPath());

                if (\in_array($itemPath, $covered, true)) {
                    continue;
                }

                $covered[] = $itemPath;

                yield $item->withPath($itemPath);
            }
        }
    }

    /**
     * Syncs all DBAFS that match all or parts of the given $paths.
     */
    public function sync(string ...$paths): ChangeSet
    {
        $dbafsAndPathsByPrefix = [];

        // Sync all DBAFS if no paths are supplied, otherwise individually match paths
        // according to the configured DBAFS prefixes
        if (!$paths) {
            foreach ($this->dbafs as $prefix => $dbafs) {
                $dbafsAndPathsByPrefix[$prefix] = [$dbafs, []];
            }
        } else {
            foreach ($paths as $path) {
                foreach ($this->getDbafsForPath(rtrim($path, '*')) as $prefix => $dbafs) {
                    $entry = $dbafsAndPathsByPrefix[$prefix] ?? [$dbafs, []];
                    $entry[1][] = Path::makeRelative($path, $prefix);
                    $dbafsAndPathsByPrefix[$prefix] = $entry;
                }
            }
        }

        // Ensure a consistent order
        ksort($dbafsAndPathsByPrefix, SORT_NATURAL);

        $changeSet = ChangeSet::createEmpty();

        foreach ($dbafsAndPathsByPrefix as $prefix => [$dbafs, $matchingPaths]) {
            $changeSet = $changeSet->withOther($dbafs->sync(...$matchingPaths), (string) $prefix);
        }

        return $changeSet;
    }

    private function getRecord(string $path): FilesystemItem|null
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        if (!$dbafs = $dbafsIterator->current()) {
            return null;
        }

        return $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key()));
    }

    /**
     * @return \Generator<string, DbafsInterface>
     */
    private function getCandidatesForPrefix(string $prefix): \Generator
    {
        foreach ($this->dbafs as $dbafsPrefix => $dbafs) {
            if (Path::isBasePath("/$prefix", "/$dbafsPrefix")) {
                yield (string) $dbafsPrefix => $dbafs;
            }
        }
    }

    /**
     * @return \Generator<string, DbafsInterface|null>
     */
    private function getDbafsForPath(string $path): \Generator
    {
        foreach ($this->dbafs as $dbafsPrefix => $dbafs) {
            if (Path::isBasePath("/$dbafsPrefix", "/$path")) {
                yield (string) $dbafsPrefix => $dbafs;
            }
        }
    }

    /**
     * Ensures that all DBAFS with a more specific prefix are also supporting
     * everything each less specific one does.
     *
     * For example, a DBAFS with prefix "files/media" must also support "fileSize" if
     * the DBAFS under "files" does. It could, however, support additional properties
     * like "mimeType" even if the "files" DBAFS does not.
     */
    private function validateTransitiveProperties(): void
    {
        $currentPrefix = '';
        $supportedFeatures = DbafsInterface::FEATURES_NONE;

        foreach (array_reverse($this->dbafs, true) as $prefix => $dbafs) {
            if (Path::isBasePath("/$currentPrefix", "/$prefix")) {
                // Find all feature flags that are required but not supported
                $nonTransitive = $supportedFeatures & ~$dbafs->getSupportedFeatures();

                if (0 !== $nonTransitive) {
                    $features = implode('" and "', $this->getFeatureFlagsAsNames($nonTransitive));

                    throw new \LogicException(\sprintf('The transitive feature(s) "%s" must be supported for any DBAFS with a path prefix "%s", because they are also supported for "%s".', $features, $prefix, $currentPrefix));
                }
            }

            $currentPrefix = $prefix;
            $supportedFeatures = $dbafs->getSupportedFeatures();
        }
    }

    /**
     * @return array<string>
     */
    private function getFeatureFlagsAsNames(int $flags): array
    {
        $reflection = new \ReflectionClass(DbafsInterface::class);
        $resolved = [];

        foreach ($reflection->getReflectionConstants() as $constant) {
            if (($constant->getValue() & $flags) && str_starts_with($name = $constant->getName(), 'FEATURE_')) {
                $resolved[] = strtolower(str_replace('_', ' ', substr($name, 8)));
            }
        }

        return $resolved;
    }
}
