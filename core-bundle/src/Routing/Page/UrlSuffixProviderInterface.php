<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Page;

interface UrlSuffixProviderInterface
{
    /**
     * URL suffixes are used to generate alias candidates, which means
     * stripping the URL suffix from a request path to find pages with
     * the remaining path as an alias.
     *
     * Only return a non-empty array if this page does not use the global
     * URL suffix as configured in the root page, e.g if this is a "feed" page type,
     * it could return ".rss" and ".atom" as the URL suffixes.
     */
    public function getUrlSuffixes(): array;
}
