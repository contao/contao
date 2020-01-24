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
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PageTypeConfigEvent;
use Contao\PageModel;
use Symfony\Component\Routing\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function in_array;
use function strtolower;

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
     * List of supported features.
     *
     * @var array
     */
    protected static $features = [
        self::FEATURE_ARTICLES
    ];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Computes the name of the page type by using unqualified classname without suffix "PageType" and converts it to
     * camelize.
     *
     * @return string
     */
    public function getName(): string
    {
        return strtolower(
            preg_replace(
                '/(?<!^)(?<![0-9])[A-Z0-9]/',
                '_$0',
                substr(strrchr(static::class, '\\'), 1, -8)
            )
        );
    }

    protected function configurePageTypeConfig(PageTypeConfigInterface $pageTypeConfig): PageTypeConfigInterface
    {
        $this->eventDispatcher->dispatch(new PageTypeConfigEvent($pageTypeConfig), ContaoCoreEvents::PAGE_TYOE_CONFIG);

        return $pageTypeConfig;
    }

    public function getAvailableParameters(): array
    {
        return array_keys(static::$parameters);
    }

    public function getRequiredParameters(): array
    {
        return [];
    }

    public function getRequirements(array $parameters): array
    {
        return array_filter(
            array_intersect_key(static::$parameters, array_flip($parameters))
        );
    }

    public function supportsFeature(string $feature) : bool
    {
        return in_array($feature, static::$features, true);
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
            'pageTypeConfig' => $this->configurePageTypeConfig(new PageTypeConfig($this, $pageModel)),
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
