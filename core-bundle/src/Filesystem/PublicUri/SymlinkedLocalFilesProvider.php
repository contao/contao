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

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SymlinkedLocalFilesProvider implements PublicUriProviderInterface
{
    /**
     * @var \WeakMap<LocalFilesystemAdapter, string>
     */
    private \WeakMap $adapters;

    public function __construct(
        LocalFilesystemAdapter $localFilesAdapter,
        readonly string $uploadDir,
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
    public function getUri(FilesystemAdapter $adapter, string $adapterPath, OptionsInterface|null $options): UriInterface|null
    {
        if ($options || null === ($rootPath = ($this->adapters[$adapter] ?? null))) {
            return null;
        }

        return new Uri(\sprintf('%s/%s/%s', $this->getSchemeAndHost(), $rootPath, $adapterPath));
    }

    private function getSchemeAndHost(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return '';
        }

        return $request->getSchemeAndHttpHost();
    }
}
