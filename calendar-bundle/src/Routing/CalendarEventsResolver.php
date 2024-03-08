<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Routing;

use Contao\ArticleModel;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;

class CalendarEventsResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof CalendarEventsModel) {
            return null;
        }

        switch ($content->source) {
            // Link to an external page
            case 'external':
                return ContentUrlResult::url($content->url);

            // Link to an internal page
            case 'internal':
                $pageAdapter = $this->framework->getAdapter(PageModel::class);

                return ContentUrlResult::redirect($pageAdapter->findPublishedById($content->jumpTo));

            // Link to an article
            case 'article':
                $articleAdapter = $this->framework->getAdapter(ArticleModel::class);

                return ContentUrlResult::redirect($articleAdapter->findPublishedById($content->articleId));
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $calendarAdapter = $this->framework->getAdapter(CalendarModel::class);

        // Link to the default page
        return ContentUrlResult::resolve($pageAdapter->findPublishedById((int) $calendarAdapter->findById($content->pid)?->jumpTo));
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof CalendarEventsModel) {
            return [];
        }

        return ['parameters' => '/'.($content->alias ?: $content->id)];
    }
}
