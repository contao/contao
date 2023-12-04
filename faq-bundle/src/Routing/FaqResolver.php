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

use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\FaqModel;
use Contao\PageModel;

class FaqResolver implements ContentUrlResolverInterface
{
    public function resolve(object $content): ContentUrlResult
    {
        if (!$content instanceof FaqModel) {
            return ContentUrlResult::abstain();
        }

        return ContentUrlResult::resolve(PageModel::findWithDetails((int) $content->getRelated('pid')?->jumpTo));
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof FaqModel) {
            return [];
        }

        return [
            'parameters' => '/'.($content->alias ?: $content->id),
        ];
    }
}
