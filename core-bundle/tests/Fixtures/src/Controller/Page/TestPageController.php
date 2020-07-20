<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Controller\Page;

use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Symfony\Component\Routing\Route;

class TestPageController implements DynamicRouteInterface, ContentCompositionInterface
{
    public function supportsContentComposition(PageModel $pageModel): bool
    {
        return false;
    }

    public function enhancePageRoute(PageRoute $route): Route
    {
        return $route;
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }
}
