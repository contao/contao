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

use Contao\CoreBundle\Routing\Page\PageRouteFactory;
use Contao\PageModel;
use Symfony\Component\Routing\Route;

class PageRouteProvider implements ContentRouteProviderInterface
{
    /**
     * @var PageRouteFactory
     */
    private $routeFactory;

    public function __construct(PageRouteFactory $routeFactory)
    {
        $this->routeFactory = $routeFactory;
    }

    /**
     * @param PageModel $page
     */
    public function getRouteForContent($page): Route
    {
        return $this->routeFactory->createRoute($page);
    }

    public function supportsContent($content): bool
    {
        return $content instanceof PageModel;
    }
}
