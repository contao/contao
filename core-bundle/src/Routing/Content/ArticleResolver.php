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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;

class ArticleResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof ArticleModel) {
            return null;
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        return ContentUrlResult::resolve($pageAdapter->findWithDetails($content->pid));
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof ArticleModel) {
            return [];
        }

        return ['parameters' => '/articles/'.($content->alias ?: $content->id)];
    }
}
