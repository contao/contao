<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

use Nyholm\Psr7\Uri;
use Symfony\Component\Filesystem\Path;

class UrlUtil
{
    /**
     * Creates an absolute URL from the given relative and base URL according to the URL standard.
     *
     * @see https://url.spec.whatwg.org/#concept-basic-url-parser
     *
     * @param string $relativeUrl Any valid URL, relative or absolute
     * @param string $baseUrl     Domain-relative (starts with a slash) or absolute URL
     */
    public static function makeAbsolute(string $relativeUrl, string $baseUrl): string
    {
        $relative = new Uri($relativeUrl);

        if ('' !== $relative->getScheme()) {
            return (string) $relative->withPath($relative->getPath() ?: '/');
        }

        $base = new Uri($baseUrl);

        if ('' !== $relative->getAuthority()) {
            return (string) $relative->withScheme($base->getScheme())->withPath($relative->getPath() ?: '/');
        }

        $path = $relative->getPath() ?: '/';
        $query = $relative->getQuery();

        if ('' === $relative->getPath()) {
            $path = $base->getPath() ?: '/';

            if ('' === $relative->getQuery()) {
                $query = $base->getQuery();
            }
        } elseif (!str_starts_with($relative->getPath(), '/')) {
            $path = Path::makeAbsolute($relative->getPath(), preg_replace('([^/]+$)', '', $base->getPath()) ?: '/');
        }

        return (string) $base->withPath($path)->withQuery($query)->withFragment($relative->getFragment());
    }

    /**
     * Makes an absolute URL relative to the given path. This will never make a protocol-relative URL, but
     * will remove the host if it matches the base URL.
     */
    public static function makeAbsolutePath(string $absoluteUrl, string $baseUrl): string
    {
        $absolute = new Uri($absoluteUrl);

        if ('' === $absolute->getAuthority()) {
            return (string) $absolute->withPath($absolute->getPath() ?: '/');
        }

        $base = new Uri($baseUrl);

        if ($base->getScheme() === $absolute->getScheme() && $base->getHost() === $absolute->getHost()) {
            $absolute = $absolute->withScheme('')->withHost('');
        }

        return (string) $absolute->withPath($absolute->getPath() ?: '/');
    }
}
