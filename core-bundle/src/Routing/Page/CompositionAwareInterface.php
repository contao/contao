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

interface CompositionAwareInterface
{
    /**
     * If the page supports content composition, it's layout is defined by a Contao
     * page layout, and it supports articles and content elements.
     *
     * Most Contao page types do support composition. Pages that do not support composition
     * can be structural (e.g. a redirect page) or functional (e.g. an XML sitemap).
     *
     * The optional $pageModel might tell if a particular page supports composition,
     * for example a 404 page that redirects cannot have articles, but a regular 404 does.
     */
    public function supportsContentComposition(PageModel $pageModel = null): bool;
}
