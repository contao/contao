<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\PageModel;
use Twig\Environment;

class PageRoutingListener
{
    private ContaoFramework $framework;
    private PageRegistry $pageRegistry;
    private Environment $twig;

    public function __construct(ContaoFramework $framework, PageRegistry $pageRegistry, Environment $twig)
    {
        $this->framework = $framework;
        $this->pageRegistry = $pageRegistry;
        $this->twig = $twig;
    }

    /**
     * @Callback(table="tl_page", target="fields.routePath.load")
     *
     * @param mixed $value
     */
    public function loadRoutePath($value, DataContainer $dc): string
    {
        $pageModel = $this->framework->getAdapter(PageModel::class)->findByPk($dc->id);

        if (null === $pageModel) {
            return '';
        }

        return $this->pageRegistry->getRoute($pageModel)->getPath();
    }

    /**
     * @Callback(table="tl_page", target="fields.routeConflicts.input_field")
     */
    public function generateRouteConflicts(DataContainer $dc): string
    {
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $currentPage = $pageAdapter->findWithDetails($dc->id);

        if (null === $currentPage) {
            return '';
        }

        $aliasPages = $pageAdapter->findSimilarByAlias($currentPage);

        if (null === $aliasPages) {
            return '';
        }

        $conflicts = [];
        $currentUrl = $this->buildUrl($currentPage->alias, $currentPage->urlPrefix, $currentPage->urlSuffix);
        $backendAdapter = $this->framework->getAdapter(Backend::class);

        foreach ($aliasPages as $aliasPage) {
            $aliasPage->loadDetails();

            if ($currentPage->domain !== $aliasPage->domain) {
                continue;
            }

            $aliasUrl = $this->buildUrl($aliasPage->alias, $aliasPage->urlPrefix, $aliasPage->urlSuffix);

            if ($currentUrl !== $aliasUrl || !$this->pageRegistry->isRoutable($aliasPage)) {
                continue;
            }

            $conflicts[] = [
                'page' => $aliasPage,
                'route' => $this->pageRegistry->getRoute($aliasPage),
                'editUrl' => $backendAdapter->addToUrl(sprintf('act=edit&id=%s&popup=1&nb=1', $aliasPage->id)),
            ];
        }

        if (empty($conflicts)) {
            return '';
        }

        return $this->twig->render(
            '@ContaoCore/Backend/be_route_conflicts.html.twig',
            [
                'conflicts' => $conflicts,
            ]
        );
    }

    /**
     * Builds the URL from prefix, alias and suffix. We cannot use the router for
     * this, since pages might have non-optional parameters. This value is only used to
     * compare two pages and see if they _might_ conflict based on the alias itself.
     */
    private function buildUrl(string $alias, string $urlPrefix, string $urlSuffix): string
    {
        $url = '/'.$alias.$urlSuffix;

        if ($urlPrefix) {
            $url = '/'.$urlPrefix.$url;
        }

        return $url;
    }
}
