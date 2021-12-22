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

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

/**
 * This class manages access to multiple DBAFS instances. Each instance can be
 * registered with a prefix path and will be queried accordingly.
 *
 * Note: In general, user code should not directly interface with the
 *       DbafsManager, but use the @see VirtualFilesystem instead.
 */
class DbafsManager
{
    /**
     * @var array<DbafsInterface>
     */
    private array $dbafs = [];

    /**
     * @internal Use the "contao.filesystem.dbafs_thingy_factory" service to create new instances. // todo: name
     */
    public function __construct()
    {
    }

    public function register(DbafsInterface $dbafs, string $pathPrefix): void
    {
        $this->dbafs[$pathPrefix] = $dbafs;
        krsort($this->dbafs);

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
    public function resourceExists(string $path): bool
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        return null !== ($dbafs = $dbafsIterator->current())
            && null !== $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key()));
    }

    /**
     * Resolves a UUID to a path. All registered DBAFS are queried until the
     * request can be fulfilled, otherwise an UnableToResolveUuidException
     * will be thrown. You can constrain querying only a subset by providing
     * a path $prefix.
     *
     * @throws UnableToResolveUuidException
     */
    public function resolveUuid(Uuid $uuid, string $prefix = ''): string
    {
        foreach ($this->getCandidatesForPrefix($prefix) as $dbafs) {
            if (null !== ($path = $dbafs->getPathFromUuid($uuid))) {
                return $path;
            }
        }

        throw new UnableToResolveUuidException($uuid);
    }

    /**
     * Returns the last modified time or null if no DBAFS exists for the given
     * $path, that supports the attribute and contains a matching record.
     */
    public function getLastModified(string $path): ?int
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        if (
            null !== ($dbafs = $dbafsIterator->current())
            && $dbafs->supportsLastModified()
            && null !== ($record = $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key())))
        ) {
            return $record->getLastModified();
        }

        return null;
    }

    /**
     * Returns the file size or null if no DBAFS exists for the given $path,
     * that supports the attribute and contains a matching record.
     */
    public function getFileSize(string $path): ?int
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        if (
            null !== ($dbafs = $dbafsIterator->current())
            && $dbafs->supportsFileSize()
            && null !== ($record = $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key())))
        ) {
            return $record->getFileSize();
        }

        return null;
    }

    /**
     * Returns the mime type or null if no DBAFS exists for the given $path,
     * that supports the attribute and contains a matching record.
     */
    public function getMimeType(string $path): ?string
    {
        $dbafsIterator = $this->getDbafsForPath($path);

        if (
            null !== ($dbafs = $dbafsIterator->current())
            && $dbafs->supportsMimeType()
            && null !== ($record = $dbafs->getRecord(Path::makeRelative($path, $dbafsIterator->key())))
        ) {
            return $record->getMimeType();
        }

        return null;
    }

    /**
     * Returns merged extra metadata from all DBAFS that are able to serve the
     * given $path.
     *
     * @return array<string, mixed>
     */
    public function getExtraMetadata(string $path): array
    {
        $metadataChunks = [];
        $metadataKeys = [];

        foreach ($this->getDbafsForPath($path) as $prefix => $dbafs) {
            if (null !== ($record = $dbafs->getRecord(Path::makeRelative($path, $prefix)))) {
                $chunk = $record->getExtraMetadata();
                $keys = array_keys($chunk);

                if (!empty($duplicates = array_intersect($metadataKeys, $keys))) {
                    throw new \LogicException(sprintf('The metadata key(s) "%s" appeared in more than one matching DBAFS for path "%s".', implode('", "', $duplicates), $path));
                }

                $metadataChunks[] = $chunk;
                $metadataKeys = [...$metadataKeys, ...$keys];
            }
        }

        return array_merge(...array_reverse($metadataChunks));
    }

    /**
     * Sets extra metadata to all DBAFS that are able to serve the given $path.
     *
     * @param array<string, mixed> $metadata
     */
    public function setExtraMetadata(string $path, array $metadata): void
    {
        $success = false;

        foreach ($this->getDbafsForPath($path) as $prefix => $dbafs) {
            $resolvedPath = Path::makeRelative($path, $prefix);

            try {
                $dbafs->setExtraMetadata($resolvedPath, $metadata);
                $success = true;
            } catch (\InvalidArgumentException $e) {
                // ignore
            }
        }

        if (!$success) {
            throw new \InvalidArgumentException("No resource exists for the given path '$path'.");
        }
    }

    /**
     * List contents from all DBAFS that are able to serve the given $path.
     * Each path is guaranteed to be only reported once, i.e. identical paths
     * from DBAFs with a lower specificity will be ignored.
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
        /** @var array<int, array{0: DbafsInterface, 1:string}> $dbafsDictionary */
        $dbafsDictionary = [];

        /** @var array<int, array<string>> $pathsForDbafs */
        $pathsForDbafs = [];

        foreach ($paths as $path) {
            foreach ($this->getDbafsForPath(rtrim($path, '*')) as $prefix => $dbafs) {
                $id = spl_object_id($dbafs);

                $dbafsDictionary[$id] = [$dbafs, $prefix];
                $pathsForDbafs[$id][] = Path::makeRelative($path, $prefix);
            }
        }

        $changeSet = ChangeSet::createEmpty();

        foreach ($pathsForDbafs as $id => $matchingPaths) {
            [$dbafs, $prefix] = $dbafsDictionary[$id];

            $changeSet = $changeSet->withOther($dbafs->sync(...$matchingPaths), $prefix);
        }

        return $changeSet;
    }

    /**
     * @return \Generator<DbafsInterface>
     */
    private function getCandidatesForPrefix(string $prefix): \Generator
    {
        foreach ($this->dbafs as $dbafsPrefix => $dbafs) {
            if (Path::isBasePath("/$prefix", "/$dbafsPrefix")) {
                yield $dbafs;
            }
        }
    }

    /**
     * @return \Generator<string, DbafsInterface>
     */
    private function getDbafsForPath(string $path): \Generator
    {
        foreach ($this->dbafs as $dbafsPrefix => $dbafs) {
            if (Path::isBasePath("/$dbafsPrefix", "/$path")) {
                yield $dbafsPrefix => $dbafs;
            }
        }
    }

    /**
     * Make sure that all DBAFS with a more specific prefix are also supporting
     * everything each less specific one does. For example a DBAFS with prefix
     * 'files/media' must also support 'fileSize' if the DBAFS under 'files'
     * does. It could, however, support additional properties like 'mimeType',
     * even if the 'files' DBAFS does not.
     */
    private function validateTransitiveProperties(): void
    {
        $supports = [
            'supportsLastModified' => false,
            'supportsFileSize' => false,
            'supportsMimeType' => false,
        ];

        $currentPrefix = '';

        foreach (array_reverse($this->dbafs) as $prefix => $dbafs) {
            if (Path::isBasePath("/$currentPrefix", "/$prefix")) {
                foreach (array_keys(array_filter($supports)) as $property) {
                    if (!$dbafs->$property()) {
                        throw new \LogicException("The transitive property '$property' must be true for any DBAFS with a path prefix '$prefix', because it its also true for '$currentPrefix'.");
                    }
                }
            }

            $currentPrefix = $prefix;

            foreach (array_keys($supports) as $property) {
                $supports[$property] = $dbafs->$property();
            }
        }
    }
}
