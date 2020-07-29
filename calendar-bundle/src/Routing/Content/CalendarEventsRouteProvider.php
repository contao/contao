<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Routing\Content;

use Contao\ArticleModel;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentRouteProviderInterface;
use Contao\CoreBundle\Routing\RouteFactory;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class CalendarEventsRouteProvider implements ContentRouteProviderInterface
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
     * @param CalendarEventsModel $event
     */
    public function getRouteForContent($event): Route
    {
        switch ($event->source) {
            // Link to an external page
            case 'external':
                return $this->getExternalRoute($event);

            // Link to an internal page
            case 'internal':
                $page = $event->getRelated('jumpTo');

                if ($page instanceof PageModel) {
                    return $this->routeFactory->createRouteForContent($page);
                }
                break;

            // Link to an article
            case 'article':
                $article = $event->getRelated('articleId');

                if ($article instanceof ArticleModel) {
                    return $this->routeFactory->createRouteForContent($article);
                }
                break;
        }

        return $this->getDefaultRoute($event);
    }

    public function supportsContent($content): bool
    {
        return $content instanceof CalendarEventsModel;
    }

    private function getExternalRoute(CalendarEventsModel $event): Route
    {
        /** @var InsertTags $insertTags */
        $insertTags = $this->framework->createInstance(InsertTags::class);

        if (0 === strncmp($event->url, 'mailto:', 7)) {
            $path = StringUtil::encodeEmail($event->url);
        } else {
            $path = $insertTags->replace($event->url);
        }

        return new Route($path);
    }

    private function getDefaultRoute(CalendarEventsModel $event): Route
    {
        $calendar = $event->getRelated('pid');

        if (!$calendar instanceof CalendarModel) {
            throw new RouteNotFoundException('Missing calendar for event ID '.$event->id);
        }

        /** @var PageModel $page */
        $page = $calendar->getRelated('jumpTo');

        if (!$page instanceof PageModel) {
            throw new RouteNotFoundException('Missing target page for calendar ID '.$calendar->id);
        }

        $page->loadDetails();

        return $this->routeFactory->createRouteForPage(
            $page,
            ($page->useAutoItem ? '/' : '/events/').($event->alias ?: $event->id),
            $event
        );
    }
}
