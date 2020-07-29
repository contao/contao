<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Routing\Content;

use Contao\ArticleModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentRouteProviderInterface;
use Contao\CoreBundle\Routing\RouteFactory;
use Contao\InsertTags;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class NewsRouteProvider implements ContentRouteProviderInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var RouteFactory
     */
    private $routeFactory;

    public function __construct(ContaoFramework $framework, RouteFactory $routeFactory)
    {
        $this->framework = $framework;
        $this->routeFactory = $routeFactory;
    }

    /**
     * @param NewsModel $news
     */
    public function getRouteForContent($news): Route
    {
        switch ($news->source) {
            case 'external':
                return $this->getExternalRoute($news);

            case 'internal':
                $page = $news->getRelated('jumpTo');

                if ($page instanceof PageModel) {
                    return $this->routeFactory->createRouteForContent($page);
                }
                break;

            case 'article':
                $article = $news->getRelated('articleId');

                if ($article instanceof ArticleModel) {
                    return $this->routeFactory->createRouteForContent($article);
                }
                break;
        }

        return $this->getDefaultRoute($news);
    }

    public function supportsContent($content): bool
    {
        return $content instanceof NewsModel;
    }

    private function getExternalRoute(NewsModel $news): Route
    {
        /** @var InsertTags $insertTags */
        $insertTags = $this->framework->createInstance(InsertTags::class);

        if (0 === strncmp($news->url, 'mailto:', 7)) {
            $path = StringUtil::encodeEmail($news->url);
        } else {
            $path = $insertTags->replace($news->url);
        }

        return new Route($path);
    }

    private function getDefaultRoute(NewsModel $news): Route
    {
        $archive = $news->getRelated('pid');

        if (!$archive instanceof NewsArchiveModel) {
            throw new RouteNotFoundException('Missing archive for news ID '.$news->id);
        }

        /** @var PageModel $page */
        $page = $archive->getRelated('jumpTo');

        if (!$page instanceof PageModel) {
            throw new RouteNotFoundException('Missing target page for news archive ID '.$archive->id);
        }

        $page->loadDetails();

        return $this->routeFactory->createRouteForPage(
            $page,
            ($page->useAutoItem ? '/' : '/items/').($news->alias ?: $news->id),
            $news
        );
    }
}
