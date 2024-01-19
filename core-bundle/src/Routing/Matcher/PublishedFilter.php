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
 * Filters routes if the page or root page has not been published and the front
 * end preview is not enabled. This will prevent redirects to unpublished
 * language root pages.
 */
class PublishedFilter implements RouteFilterInterface
{
    public function filter(RouteCollection $collection, Request $request): RouteCollection
    {
        // Route all pages in the preview entry point
        if ($request->attributes->get('_preview', false)) {
            return $collection;
        }

        foreach ($collection->all() as $name => $route) {
            $pageModel = $route->getDefault('pageModel');

            if (!$pageModel instanceof PageModel) {
                continue;
            }

            if (!$pageModel->isPublic || !$pageModel->rootIsPublic) {
                $collection->remove($name);
            }
        }

        return $collection;
    }
}
