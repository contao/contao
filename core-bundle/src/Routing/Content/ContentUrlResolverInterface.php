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

use Contao\PageModel;

interface ContentUrlResolverInterface
{
    /**
     * Returns a decision for resolving the given content.
     * - ResolverDecision::abstain() if it cannot handle the content.
     * - ResolverDecision::redirectToUrl() if the content has a URL string that could be relative or contain insert tags.
     * - ResolverDecision::redirectToContent() to generate the URL for a new content instead of the current one.
     * - ResolverDecision::resolve() to generate the URL for the given PageModel with the current content.
     */
    public function resolve(object $content): ResolverDecision;

    /**
     * Returns an array of parameters for the given content that can be used
     * to generate a URL for this content. If the parameter is used in the page alias,
     * it will be used to generate the URL. Otherwise, it is ignored (contrary to the Symfony
     * URL generator which would add it as a query parameter).
     *
     * @return array<string, string|int>
     */
    public function getParametersForContent(object $content, PageModel $pageModel): array;
}
