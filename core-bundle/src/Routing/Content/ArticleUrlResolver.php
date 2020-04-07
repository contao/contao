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
use Contao\PageModel;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class ArticleUrlResolver implements ContentUrlResolverInterface
{
    /**
     * @param ArticleModel $article
     */
    public function resolveContent($article): Route
    {
        /** @var PageModel $page */
        $page = $article->getRelated('pid');

        if (!$page instanceof PageModel) {
            throw new RouteNotFoundException(sprintf('Page ID %s for article ID %s not found', $article->id, $article->pid));
        }

        return new PageRoute($page, '/articles/'.($article->alias ?: $article->id), $article);
    }

    public function supportsContent($content): bool
    {
        return $content instanceof ArticleModel;
    }
}
