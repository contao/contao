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

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Nyholm\Psr7\Uri;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;

class UrlUtil
{
    public function __construct(
        private readonly InsertTagParser $insertTagParser,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function parseContaoUrl(string $url): string
    {
        $url = $this->insertTagParser->replaceInline($url);

        // Ensure absolute links
        if (!preg_match('#^https?://#', $url)) {
            if (!$request = $this->requestStack->getCurrentRequest()) {
                throw new \RuntimeException('The request stack did not contain a request');
            }

            $url = static::makeAbsolute($url, $request->getUri());
        }

        return $url;
    }

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
}
