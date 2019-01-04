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

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

class PublishingFilter implements RouteFilterInterface
{
    /**
     * @var TokenChecker
     */
    private $tokenChecker;

    public function __construct(TokenChecker $tokenChecker)
    {
        $this->tokenChecker = $tokenChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(RouteCollection $collection, Request $request): RouteCollection
    {
        if ($this->tokenChecker->isPreviewMode()) {
            return $collection;
        }

        foreach ($collection->all() as $name => $route) {
            if (!$route->getDefault('pageModel') instanceof PageModel) {
                continue;
            }

            /** @var PageModel $page */
            $page = $route->getDefault('pageModel');
            $time = time();

            if (
                !$page->published
                || ('' !== $page->start && $page->start > $time)
                || ('' !== $page->stop && $page->stop < $time)
            ) {
                $collection->remove($name);
            }
        }

        return $collection;
    }
}
