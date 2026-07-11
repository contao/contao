<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Routing;

use Contao\ArticleModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;

class NewsResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof NewsModel) {
            return null;
        }

        switch ($content->source) {
            // Link to an external page
            case 'external':
                return ContentUrlResult::url($content->url);

            // Link to an internal page
            case 'internal':
                $pageAdapter = $this->framework->getAdapter(PageModel::class);

                return ContentUrlResult::redirect($pageAdapter->findById($content->jumpTo));

            // Link to an article
            case 'article':
                $articleAdapter = $this->framework->getAdapter(ArticleModel::class);

                return ContentUrlResult::redirect($articleAdapter->findById($content->articleId));
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $archiveAdapter = $this->framework->getAdapter(NewsArchiveModel::class);

        // Link to the default page
        return ContentUrlResult::resolve($pageAdapter->findById((int) $archiveAdapter->findById($content->pid)?->jumpTo));
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof NewsModel) {
            return [];
        }

        return ['parameters' => '/'.($content->alias ?: $content->id)];
    }
}
