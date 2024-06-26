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
     * Returns a result for resolving the given content.
     *
     * - ContentUrlResult::url() if the content has a URL string that could be relative or contain insert tags.
     * - ContentUrlResult::redirect() to generate the URL for a new content instead of the current one.
     * - ContentUrlResult::resolve() to generate the URL for the given PageModel with the current content.
     *
     * Return NULL if you cannot handle the content.
     */
    public function resolve(object $content): ContentUrlResult|null;

    /**
     * Returns an array of parameters for the given content that can be used to
     * generate a URL for this content. If the parameter is used in the page alias, it
     * will be used to generate the URL. Otherwise, it is ignored (contrary to the
     * Symfony URL generator which would add it as a query parameter).
     *
     * @return array<string, string|int>
     */
    public function getParametersForContent(object $content, PageModel $pageModel): array;
}
