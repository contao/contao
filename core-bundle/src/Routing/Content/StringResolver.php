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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\RequestContext;

class StringResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly InsertTagParser $insertTagParser,
        private readonly UrlHelper $urlHelper,
        private readonly RequestStack $requestStack,
        private readonly RequestContext $requestContext,
    ) {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof StringUrl) {
            return null;
        }

        $url = $this->insertTagParser->replaceInline($content->value);

        if ('' === $url) {
            throw new ForwardPageNotFoundException();
        }

        if (!parse_url($url, PHP_URL_SCHEME)) {
            $url = $this->urlHelper->getAbsoluteUrl($url);
        }

        // Resolve protocol-relative URLs that are ignored by the UrlHelper
        if (str_starts_with($url, '//')) {
            $protocol = $this->requestStack->getCurrentRequest()?->getScheme() ?? $this->requestContext->getScheme();
            $url = $protocol.':'.$url;
        }

        return new ContentUrlResult($url);
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        return [];
    }
}
