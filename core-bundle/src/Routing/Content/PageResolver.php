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

    public function resolve(object $content): ResolverDecision
    {
        if (!$content instanceof PageModel) {
            return ResolverDecision::abstain();
        }

        switch ($content->type) {
            case 'redirect':
                return ResolverDecision::redirectToUrl($content->url);

            case 'forward':
                $pageAdapter = $this->framework->getAdapter(PageModel::class);

                if ($content->jumpTo) {
                    $forwardPage = $pageAdapter->findPublishedById($content->jumpTo);
                } else {
                    $forwardPage = $pageAdapter->findFirstPublishedRegularByPid($content->id);
                }

                return ResolverDecision::redirectToContent($forwardPage);
        }

        return ResolverDecision::abstain();
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        return [];
    }
}
