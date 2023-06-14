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
    public static function makeAbsolute(string $relativeUrl, string $baseUrl): string
    {
        if ('' === $relativeUrl) {
            return $baseUrl;
        }

        $relative = new Uri($relativeUrl);
        $base = new Uri($baseUrl);

        if ('' !== $relative->getScheme()) {
            return $relativeUrl;
        }

        if ('' !== $relative->getAuthority()) {
            return (string) $relative->withScheme($base->getScheme());
        }

        $path = $relative->getPath() ?: '/';
        $query = $relative->getQuery();

        if (!str_starts_with($relative->getPath(), '/')) {
            if ('' === $relative->getPath()) {
                $path = $base->getPath() ?: '/';

                if ('' === $relative->getQuery()) {
                    $query = $base->getQuery();
                }
            } else {
                $path = Path::makeAbsolute($relative->getPath(), preg_replace('([^/]+$)', '', $base->getPath()) ?: '/');
            }
        }

        return (string) $base->withPath($path)->withQuery($query)->withFragment($relative->getFragment());
    }
}
