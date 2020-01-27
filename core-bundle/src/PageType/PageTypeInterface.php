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
     * Get map of available parameters which might be used in the page alias
     *
     * The key defines the parameter name, the value the requirement declared as mysql regular expression
     */
    public function getAvailableAliasParameters(): array;

    /**
     * Get list of required parameters which has to be used in the page alias
     *
     * @return array
     */
    public function getRequiredAliasParameters(): array;

    /**
     * Get map of routes created for the current page
     *
     * @return iterable|Route[]
     */
    public function getRoutes(PageModel $pageModel, bool $prependLocale, string $urlSuffix): iterable;

    /**
     * Add support for feature by name.
     *
     * This may be used by extensions to assign own features to separate page types.
     *
     * @param string $feature
     */
    public function supportFeature(string $feature): void;

    /**
     * Check if a specific feature is supported.
     *
     * @param string $feature
     *
     * @return bool
     */
    public function supportsFeature(string $feature): bool;
}
