<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

use Contao\PageModel;
use Symfony\Component\Routing\Route;

interface PageTypeInterface
{
    public const FEATURE_ARTICLES = 'articles';
    public const FEATURE_ARTICLE_VIEW = 'article-view';

    public function getName(): string;

    /**
     * Get available parameters which might be used in the page alias
     */
    public function getAvailableParameters(): array;

    /**
     * Get list of required parameters which has to be used in the page alias
     *
     * @return array
     */
    public function getRequiredParameters(): array;

    /**
     * Get map of routes created for the current page
     *
     * @return iterable|Route[]
     */
    public function getRoutes(PageModel $pageModel, bool $prependLocale, string $urlSuffix): iterable;

    public function supportsFeature(string $feature): bool;
}
