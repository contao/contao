<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Candidates;

use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\HttpFoundation\Request;

class AbstractCandidates implements CandidatesInterface
{
    /**
     * A limit to apply to the number of candidates generated.
     *
     * This is to prevent abusive requests with a lot of "/". The limit is per
     * batch, that is if a locale matches you could get as many as 2 * $limit
     * candidates if the URL has that many slashes.
     */
    private const LIMIT = 20;

    public function __construct(protected array $urlPrefixes, protected array $urlSuffixes)
    {
    }

    /**
     * @param string $name
     */
    public function isCandidate($name): bool
    {
        return str_starts_with($name, 'tl_page.');
    }

    public function restrictQuery($queryBuilder): void
    {
    }

    /**
     * Generates possible page aliases from the request path by removing
     * prefixes, suffixes and parameters.
     *
     * Example 1:
     *   Path: /en/alias/foo/bar.html
     *   Prefixes: [en, de]
     *   Suffixes: [.html]
     *   Possible aliases:
     *     - alias/foo/bar
     *     - alias/foo
     *     - alias
     *
     * Example 2:
     *   Path: /en/alias/foo/bar.html
     *   Prefixes: [en, '']
     *   Suffixes: [.html, '']
     *   Possible aliases:
     *     - en/alias/foo/bar.html
     *     - en/alias/foo/bar
     *     - en/alias/foo
     *     - en/alias
     *     - alias/foo/bar.html
     *     - alias/foo/bar
     *     - alias/foo
     *     - alias
     */
    public function getCandidates(Request $request): array
    {
        $url = $request->getPathInfo();
        $url = rawurldecode(substr($url, 1));

        if ('' === $url) {
            throw new \RuntimeException(__METHOD__.' cannot handle empty path');
        }

        $candidates = [];

        foreach ($this->urlPrefixes as $prefix) {
            // Language prefix only (e.g. URL = /en/)
            // Includes "/en" to redirect to language prefix with slash
            if ($url === $prefix.'/' || $url === $prefix) {
                $candidates[] = 'index';
                continue;
            }

            $withoutPrefix = $url;

            if ('' !== $prefix) {
                if (0 !== strncmp($url, $prefix.'/', \strlen((string) $prefix) + 1)) {
                    continue;
                }

                $withoutPrefix = substr($url, \strlen((string) $prefix) + 1);
            }

            foreach ($this->urlSuffixes as $suffix) {
                $withoutSuffix = $withoutPrefix;

                if ('' !== $suffix) {
                    if (!str_ends_with($withoutPrefix, $suffix)) {
                        continue;
                    }

                    $withoutSuffix = substr($withoutPrefix, 0, -\strlen((string) $suffix));
                }

                $this->addCandidatesFor($withoutSuffix, $candidates);
            }
        }

        return array_values(array_unique($candidates));
    }

    private function addCandidatesFor(string $url, array &$candidates): void
    {
        if ('' === $url) {
            $candidates[] = 'index';

            return;
        }

        $part = $url;
        $count = 0;

        while (false !== ($pos = strrpos($part, '/'))) {
            ++$count;

            if ($count > self::LIMIT) {
                return;
            }

            $candidates[] = $part;
            $part = substr($url, 0, $pos);
        }

        $candidates[] = $part;
    }
}
