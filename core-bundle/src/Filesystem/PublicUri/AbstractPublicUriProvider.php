<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\PublicUri;

use Contao\CoreBundle\Util\UrlUtil;
use League\Flysystem\FilesystemAdapter;
use Psr\Http\Message\UriInterface;

abstract class AbstractPublicUriProvider
{
    public const VERSION_QUERY_PARAMETER = 'version';

    protected function versionizeUri(UriInterface $uri, FilesystemAdapter $adapter, string $adapterPath, Options|null $options = null): UriInterface
    {
        if (true !== $options?->get(Options::OPTION_ADD_VERSION_QUERY_PARAMETER)) {
            return $uri;
        }

        $version = $this->getVersionParameter($adapter, $adapterPath);

        if (null === $version) {
            return $uri;
        }

        return UrlUtil::mergeQueryIfMissing($uri, self::VERSION_QUERY_PARAMETER.'='.$version);
    }

    protected function getVersionParameter(FilesystemAdapter $adapter, string $adapterPath): string|null
    {
        try {
            $mtime = $adapter->lastModified($adapterPath)->lastModified();
        } catch (\Throwable) {
            $mtime = null;
        }

        // Hash because nobody needs to know the mtime
        return hash('xxh3', (string) $mtime);
    }
}
