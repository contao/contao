<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Matcher;

use Contao\PageModel;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Removes fallback routes if the accepted languages do not match (see #430).
 */
class LanguageFilter implements RouteFilterInterface
{
    /**
     * @var bool
     */
    private $prependLocale;

    public function __construct(bool $prependLocale)
    {
        $this->prependLocale = $prependLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(RouteCollection $collection, Request $request): RouteCollection
    {
        $languages = $request->getLanguages();

        foreach ($collection->all() as $name => $route) {
            if ('.fallback' !== substr($name, -9) && ($this->prependLocale || '.root' !== substr($name, -5))) {
                continue;
            }

            /** @var PageModel $pageModel */
            $pageModel = $route->getDefault('pageModel');

            if (
                !$pageModel instanceof PageModel
                || $pageModel->rootIsFallback
                || \in_array(str_replace('-', '_', $pageModel->rootLanguage), $languages, true)
                || preg_grep('/'.preg_quote($pageModel->rootLanguage, '/').'_[A-Z]{2}/', $languages)
            ) {
                continue;
            }

            $collection->remove($name);
        }

        return $collection;
    }
}
