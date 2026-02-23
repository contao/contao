<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\PublicUri;

use Contao\CoreBundle\Util\UrlUtil;
use League\Flysystem\FilesystemAdapter;
use Psr\Http\Message\UriInterface;

abstract class AbstractPublicUriProvider
{
    protected const VERSION_QUERY_PARAMETER = 'version';

    /**
     * @param \Closure():(string|null) $getVersionParameter
     */
    protected function versionizeUri(UriInterface $uri, Options|null $options, \Closure $getVersionParameter): UriInterface
    {
        if (true !== $options->get(Options::OPTION_ADD_VERSION_QUERY_PARAMETER)) {
            return $uri;
        }

        $version = $getVersionParameter();

        if (null === $version) {
            return $uri;
        }

        return UrlUtil::mergeQueryIfMissing($uri, self::VERSION_QUERY_PARAMETER.'='.$version);
    }

    protected function getVersionParameterFromMtimeClosure(FilesystemAdapter $adapter, string $adapterPath): \Closure
    {
        return static function () use ($adapter, $adapterPath) {
            try {
                $mtime = $adapter->lastModified($adapterPath)->lastModified();
            } catch (\Throwable) {
                $mtime = null;
            }

            // Hash because nobody needs to know the mtime
            return hash('xxh3', (string) $mtime);
        };
    }
}
