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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\PageModel;
use Symfony\Component\Routing\Route;

abstract class AbstractSinglePageType extends AbstractPageType
{
    public function getPageIdsFromRouteNames(array $names): ?array
    {
        $pageIds = [];

        foreach ($names as $name) {
            if (0 !== strncmp($name, 'tl_page.', 8)) {
                continue;
            }

            [, $id] = explode('.', $name);

            if (!is_numeric($id)) {
                continue;
            }

            $pageIds[] = (int) $id;
        }

        if (count($pageIds) === 0) {
            return null;
        }

        return $pageIds;
    }

    public function getRoutes(PageModel $pageModel, bool $prependLocale, string $urlSuffix): iterable
    {
        yield 'tl_page.'.$pageModel->id => new Route(
            $this->getRoutePath($pageModel, $prependLocale, $urlSuffix),
            $this->getRouteDefaults($pageModel),
            $this->getRouteRequirements($pageModel),
            [],
            $pageModel->domain,
            $pageModel->rootUseSSL ? 'https' : null,
            []
        );
    }

    protected function getRoutePath(PageModel $pageModel, bool $prependLocale, string $urlSuffix): string
    {
        $path = sprintf('/%s{parameters}%s', $pageModel->alias ?: $pageModel->id, $urlSuffix);

        if ($prependLocale) {
            $path = '/{_locale}'.$path;
        }

        return $path;
    }

    protected function getRouteDefaults(PageModel $pageModel): array
    {
        return [
            '_token_check' => true,
            '_controller' => 'Contao\FrontendIndex::renderPage',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
            '_locale' => $pageModel->rootLanguage,
            'pageModel' => $pageModel,
            'pageTypeConfig' => new PageTypeConfig($this, $pageModel),
        ];
    }
}
