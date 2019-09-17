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

use Contao\CoreBundle\Filesystem\Dbafs\DbafsStorageInterface;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Webmozart\PathUtil\Path;

class Storage implements DbafsStorageInterface
{
    public const MARKER_FILE__EXCLUDED = '.nosync';
    public const FILE_SIZE_MAX = 2 * 1024 * 1024; // 2GB

    /** @var FilesystemInterface */
    private $filesystem;

    /**
     * Storage constructor.
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function listSynchronizablePaths(string $scope = ''): \Traversable
    {
        $scope = rtrim($scope, '/');

        // Performance note:
        //   The Flysystem currently lacks an iterator - should this ever be a
        //   bottleneck, a plugin might be used to add that functionality.
        //   (e.g. see  https://github.com/jhofm/flysystem-iterator)
        $paths = $this->getPathsRecursively($scope);

        if ('' !== $scope) {
            // add parent paths
            do {
                $paths[] = $scope.'/';
            } while ('.' !== ($scope = \dirname($scope)));
        }

        return new \ArrayIterator($paths);
    }

    /**
     * {@inheritdoc}
     */
    public function excludeFromSync(string $path): void
    {
        $markerFilePath = $this->getMarkerFilePath($path);

        try {
            $this->filesystem->write($markerFilePath, null);
        } catch (FileExistsException $e) {
            throw new \InvalidArgumentException("Resource is already explicitly excluded from sync. See: '$markerFilePath'");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function includeToSync(string $path): void
    {
        $markerFilePath = $this->getMarkerFilePath($path);

        try {
            $this->filesystem->delete($markerFilePath);
        } catch (FileNotFoundException $e) {
            if ($this->isExcludedFromSync($path)) {
                throw new \InvalidArgumentException("The sync exclusion of '$path' is inherited and therefore cannot be removed.");
            }

            throw new \InvalidArgumentException("The resource '$path' is not excluded from sync.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isExcludedFromSync(string $path): bool
    {
        do {
            $markerFilePath = $this->getMarkerFilePath($path);

            if ($this->filesystem->has($markerFilePath)) {
                return true;
            }
        } while ('.' !== ($path = \dirname($markerFilePath, 2)));

        return false;
    }

    private function getPathsRecursively($path = '', $level = 0): array
    {
        $foundPaths = [];

        foreach ($this->filesystem->listContents($path) as $metadata) {
            $currentPath = $metadata['path'];
            $currentName = $metadata['basename'];

            if ('file' === $metadata['type']) {
                // ignore directory if file marker is present (ignore on root level)
                if (0 !== $level && 0 === strpos($currentName, self::MARKER_FILE__EXCLUDED)) {
                    break;
                }

                if (0 === strpos($currentName, '.')) {
                    continue;
                }

                if ($this->filesystem->getSize($currentPath) > self::FILE_SIZE_MAX) {
                    continue;
                }

                $foundPaths[] = $currentPath;
            } else {
                foreach ($this->getPathsRecursively($currentPath, $level + 1) as $childPath) {
                    $foundPaths[] = $childPath;
                }

                $foundPaths[] = $currentPath.'/';
            }
        }

        return $foundPaths;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getMarkerFilePath(string $path): string
    {
        if (!$this->filesystem->has($path)) {
            throw new FileNotFoundException("Resource '$path' does not exist.");
        }

        $metadata = $this->filesystem->getMetadata($path);
        if ('file' === $metadata['type']) {
            $path = \dirname($metadata['path']);
        }

        return Path::join([$path, self::MARKER_FILE__EXCLUDED]);
    }
}
