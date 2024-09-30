<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;

class FaqResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof FaqModel) {
            return null;
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $categoryAdapter = $this->framework->getAdapter(FaqCategoryModel::class);

        return ContentUrlResult::resolve($pageAdapter->findPublishedById((int) $categoryAdapter->findById($content->pid)?->jumpTo));
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof FaqModel) {
            return [];
        }

        return ['parameters' => '/'.($content->alias ?: $content->id)];
    }
}
