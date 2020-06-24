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

use Symfony\Component\HttpFoundation\Request;

class LegacyCandidates extends Candidates
{
    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * @var string
     */
    private $urlSuffix;

    public function __construct(bool $prependLocale, string $urlSuffix)
    {
        $this->prependLocale = $prependLocale;
        $this->urlSuffix = $urlSuffix;
    }

    public function getCandidates(Request $request)
    {
        $url = $request->getPathInfo();
        $url = rawurldecode(ltrim($url, '/'));
        $candidates = [];

        $url = $this->removeSuffixAndLanguage($url);

        if (null === $url) {
            return [];
        }

        $this->addCandidatesFor($url, $candidates);

        return array_values(array_unique($candidates));
    }

    private function removeSuffixAndLanguage(string $pathInfo): ?string
    {
        $suffixLength = \strlen($this->urlSuffix);

        if (0 !== $suffixLength) {
            if (substr($pathInfo, -$suffixLength) !== $this->urlSuffix) {
                return null;
            }

            $pathInfo = substr($pathInfo, 0, -$suffixLength);
        }

        if ($this->prependLocale) {
            $matches = [];

            if (!preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.+)$@', $pathInfo, $matches)) {
                return null;
            }

            $pathInfo = $matches[3];
        }

        return $pathInfo;
    }
}
