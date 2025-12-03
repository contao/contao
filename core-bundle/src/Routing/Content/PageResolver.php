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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PageRegistry $pageRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof PageModel) {
            return null;
        }

        switch ($content->type) {
            case 'root':
                $route = $this->pageRegistry->getRoute($content);
                $route->setPath('');
                $route->setUrlSuffix('');

                $url = $this->urlGenerator->generate(
                    PageRoute::PAGE_BASED_ROUTE_NAME,
                    [RouteObjectInterface::ROUTE_OBJECT => $route],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                );

                return ContentUrlResult::url($url);

            case 'redirect':
                return ContentUrlResult::url($content->url);

            case 'forward':
                $pageAdapter = $this->framework->getAdapter(PageModel::class);

                if ($content->jumpTo) {
                    $forwardPage = $pageAdapter->findById($content->jumpTo);
                } else {
                    $forwardPage = $pageAdapter->findFirstPublishedRegularByPid($content->id);
                }

                return ContentUrlResult::redirect($forwardPage);
        }

        return null;
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        return [];
    }
}
