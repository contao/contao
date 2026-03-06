<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\PublicUri;

use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SymlinkedLocalFilesProvider extends AbstractPublicUriProvider implements PublicUriProviderInterface
{
    /**
     * @var \WeakMap<LocalFilesystemAdapter, string>
     */
    private \WeakMap $adapters;

    public function __construct(
        LocalFilesystemAdapter $localFilesAdapter,
        string $uploadDir,
        private readonly RequestStack $requestStack,
    ) {
        $this->adapters = new \WeakMap();
        $this->registerAdapter($localFilesAdapter, $uploadDir);
    }

    /**
     * @internal
     */
    public function registerAdapter(LocalFilesystemAdapter $adapter, string $rootPath): void
    {
        $this->adapters[$adapter] = $rootPath;
    }

    /**
     * Generates public URLs for the symlinked local files, so that they can be
     * provided directly by the web server.
     */
    public function getUri(FilesystemAdapter $adapter, string $adapterPath, Options|null $options): UriInterface|null
    {
        if (null === ($rootPath = ($this->adapters[$adapter] ?? null))) {
            return null;
        }

        // If it's not a file, return null
        if (!$adapter->fileExists($adapterPath)) {
            return null;
        }

        if (!$this->isPublic($adapter, $adapterPath)) {
            return null;
        }

        $uri = new Uri(\sprintf('%s/%s/%s', $this->getSchemeAndHost(), $rootPath, $adapterPath));

        return $this->versionizeUri($uri, $adapter, $adapterPath, $options);
    }

    private function getSchemeAndHost(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return '';
        }

        return $request->getSchemeAndHttpHost();
    }

    private function isPublic(FilesystemAdapter $adapter, string $adapterPath): bool
    {
        $pathChunks = explode('/', $adapterPath);
        array_pop($pathChunks);

        // TODO: Can we find a more performant way of doing this?
        return array_any($pathChunks, static fn ($pathChunk) => $adapter->directoryExists($pathChunk) && $adapter->fileExists($pathChunk.'/'.Dbafs::FILE_MARKER_PUBLIC));
    }
}
