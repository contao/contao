<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;

class AbstractFeedPageController extends AbstractController implements DynamicRouteInterface
{
    public static array $contentTypes = [
        'atom' => 'application/atom+xml',
        'json' => 'application/feed+json',
        'rss' => 'application/rss+xml',
    ];

    private array $urlSuffixes = [
        'atom' => '.xml',
        'json' => '.json',
        'rss' => '.xml',
    ];

    public function configurePageRoute(PageRoute $route): void
    {
        $format = $route->getPageModel()->feedFormat;

        if (!isset($this->urlSuffixes[$format])) {
            throw new \RuntimeException(\sprintf('%s is not a valid format. Must be one of: %s', $format, implode(',', array_keys($this->urlSuffixes))));
        }

        $route->setUrlSuffix($this->urlSuffixes[$format]);
    }

    public function getUrlSuffixes(): array
    {
        return array_unique(array_values($this->urlSuffixes));
    }
}
