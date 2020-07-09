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

use Contao\ArticleModel;
use Contao\CoreBundle\Routing\RouteFactory;
use Contao\PageModel;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class ArticleRouteProvider implements ContentRouteProviderInterface
{
    /**
     * @var RouteFactory
     */
    private $routeFactory;

    public function __construct(RouteFactory $routeFactory)
    {
        $this->routeFactory = $routeFactory;
    }

    /**
     * @param ArticleModel $article
     */
    public function getRouteForContent($article): Route
    {
        /** @var PageModel $page */
        $page = $article->getRelated('pid');

        if (!$page instanceof PageModel) {
            throw new RouteNotFoundException(sprintf('Page ID %s for article ID %s not found', $article->pid, $article->id));
        }

        return $this->routeFactory->createRouteForPage($page, '/articles/'.($article->alias ?: $article->id), $article);
    }

    public function supportsContent($content): bool
    {
        return $content instanceof ArticleModel;
    }
}
