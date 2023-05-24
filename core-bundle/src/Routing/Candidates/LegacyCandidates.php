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

class LegacyCandidates extends AbstractCandidates
{
    private bool $prependLocale;

    /**
     * @internal
     */
    public function __construct(bool $prependLocale, string $urlSuffix)
    {
        parent::__construct([], [$urlSuffix]);

        $this->prependLocale = $prependLocale;
    }

    /**
     * Override the URL prefix based on the current request.
     */
    public function getCandidates(Request $request): array
    {
        $prefix = $this->getPrefixFromUrl($request);

        if (null === $prefix) {
            return [];
        }

        $this->urlPrefixes = [$prefix];

        return parent::getCandidates($request);
    }

    private function getPrefixFromUrl(Request $request): ?string
    {
        if (!$this->prependLocale) {
            return '';
        }

        $url = $request->getPathInfo();
        $url = rawurldecode(ltrim($url, '/'));

        $matches = [];

        if (!preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.+)$@', $url, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
