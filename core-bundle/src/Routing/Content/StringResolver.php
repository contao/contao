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

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\PageModel;
use Symfony\Component\Routing\RequestContext;

class StringResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly InsertTagParser $insertTagParser,
        private readonly RequestContext $requestContext,
    ) {
    }

    public function resolve(object $content): ContentUrlResult
    {
        if (!$content instanceof StringUrl) {
            return ContentUrlResult::abstain();
        }

        $url = $this->insertTagParser->replaceInline($content->value);
        $url = UrlUtil::makeAbsolute($url, $this->requestContext->getBaseUrl());

        return new ContentUrlResult($url);
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        return [];
    }
}
