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

abstract class AbstractPageType implements PageTypeInterface
{
    /**
     * Map of parameter name and it's requirement.
     *
     * If no special requirement is given
     *
     * @var array
     */
    protected static $parameters = [];

    /**
     * Computes the name of the page type by using unqualified classname without suffix "PageType" and lowercase first
     * char.
     *
     * @return string
     */
    public function getName(): string
    {
        return lcfirst(substr(strrchr(static::class, '\\'), 1, -8));
    }

    public function getControllerInformation()
    {
        return 'Contao\FrontendIndex::renderPage';
    }

    public function getAvailableParameters(): array
    {
        return array_keys(static::$parameters);
    }

    public function getRequirements(array $parameters): array
    {
        return array_filter(
            array_intersect_key(static::$parameters, array_flip($parameters))
        );
    }

    public function createRoute(PageModel $pageModel, bool $prependLocale, string $urlSuffix): Route
    {
        return new Route(
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
            'pageTypeConfig' => $this->createPageTypeConfig($pageModel)
        ];
    }

    protected function getRouteRequirements(PageModel $pageModel): array
    {
        if (0 === preg_match_all('#{([^}]+)}#', $pageModel->alias, $matches)) {
            return [];
        }

        $unsupported = array_diff($matches[1], array_keys(static::$parameters));
        if (count($unsupported) > 0) {
            throw InvalidPageAliasException::withInvalidParameters($unsupported);
        }

        return array_intersect_key(static::$parameters, array_flip($matches[1]));
    }
}
