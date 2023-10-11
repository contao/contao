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

use Contao\CoreBundle\Util\LocaleUtil;
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
     * @internal
     */
    public function __construct()
    {
    }

    public function filter(RouteCollection $collection, Request $request): RouteCollection
    {
        $languages = $request->getLanguages();

        foreach ($collection->all() as $name => $route) {
            /** @var PageModel $pageModel */
            $pageModel = $route->getDefault('pageModel');

            if (!$pageModel instanceof PageModel) {
                continue;
            }

            if ('.fallback' !== substr($name, -9) && ('' !== $pageModel->urlPrefix || '.root' !== substr($name, -5) || '/' === $route->getPath())) {
                continue;
            }

            if (
                $pageModel->rootIsFallback
                || preg_grep('/^'.LocaleUtil::getPrimaryLanguage($pageModel->rootLanguage).'/', $languages)
            ) {
                continue;
            }

            $collection->remove($name);
        }

        return $collection;
    }
}
