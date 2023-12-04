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

use Contao\PageModel;

/**
 * The ContentCompositionInterface allows a page to dynamically determine if
 * the given PageModel supports content composition. If the value is always the
 * same, use the service tag or "contentComposition=false" attribute instead.
 */
interface ContentCompositionInterface
{
    /**
     * If the page supports content composition, its layout is defined by a
     * Contao page layout and it supports articles and content elements.
     *
     * Most Contao page types do support composition. Pages that do not support
     * composition can be structural (e.g. a redirect page) or functional (e.g.
     * an XML sitemap).
     *
     * The $pageModel might tell if a particular page supports composition, for
     * example a 404 page that redirects cannot have articles, but a regular
     * 404-page can.
     */
    public function supportsContentComposition(PageModel $pageModel): bool;
}
