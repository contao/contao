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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;

class PageResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof PageModel) {
            return null;
        }

        switch ($content->type) {
            case 'redirect':
                return ContentUrlResult::url($content->url);

            case 'forward':
                $pageAdapter = $this->framework->getAdapter(PageModel::class);

                if ($content->jumpTo) {
                    $forwardPage = $pageAdapter->findPublishedById($content->jumpTo);
                } else {
                    $forwardPage = $pageAdapter->findFirstPublishedRegularByPid($content->id);
                }

                return ContentUrlResult::redirect($forwardPage);
        }

        return null;
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        return [];
    }
}
