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

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\PageModel;

class PageResolver implements ContentUrlResolverInterface
{
    public function resolve(object $content): ContentUrlResult
    {
        if (!$content instanceof PageModel) {
            return ContentUrlResult::abstain();
        }

        switch ($content->type) {
            case 'redirect':
                if (!$content->url) {
                    throw new ForwardPageNotFoundException('Invalid target URL for redirect page ID '.$content->id);
                }

                return ContentUrlResult::redirect(new StringUrl($content->url));

            case 'forward':
                if ($content->jumpTo) {
                    $forwardPage = PageModel::findPublishedById($content->jumpTo);
                } else {
                    $forwardPage = PageModel::findFirstPublishedRegularByPid($content->id);
                }

                if (!$forwardPage) {
                    throw new ForwardPageNotFoundException();
                }

                return ContentUrlResult::redirect($forwardPage);
        }

        return ContentUrlResult::abstain();
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        return [];
    }
}
