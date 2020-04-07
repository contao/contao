<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Content;

use Contao\PageModel;
use Symfony\Component\Routing\Route;

class PageUrlResolver implements ContentUrlResolverInterface
{
    /**
     * @param PageModel $page
     */
    public function resolveContent($page): Route
    {
        return PageRoute::createWithParameters($page);
    }

    public function supportsContent($content): bool
    {
        return $content instanceof PageModel;
    }
}
