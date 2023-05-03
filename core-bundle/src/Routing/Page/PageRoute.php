<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Page;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

class PageRoute extends Route implements RouteObjectInterface
{
    final public const PAGE_BASED_ROUTE_NAME = 'page_routing_object';

    private PageModel $pageModel;
    private string|null $urlPrefix;
    private string|null $urlSuffix;

    /**
     * The referenced content object (can be anything).
     */
    private mixed $content = null;

    /**
     * @param string|array<string> $methods
     */
    public function __construct(PageModel $pageModel, string $path = '', array $defaults = [], array $requirements = [], array $options = [], $methods = [])
    {
        $pageModel->loadDetails();

        $defaults = array_merge(
            [
                '_token_check' => true,
                '_controller' => 'Contao\FrontendIndex::renderPage',
                '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
                '_locale' => LocaleUtil::formatAsLocale($pageModel->rootLanguage ?? ''),
                '_format' => 'html',
                '_canonical_route' => 'tl_page.'.$pageModel->id,
            ],
            $defaults
        );

        // Always use the given page model in the defaults
        $defaults['pageModel'] = $pageModel;

        if (!isset($options['utf8'])) {
            $options['utf8'] = true;
        }

        if (!isset($options['compiler_class'])) {
            $options['compiler_class'] = PageRouteCompiler::class;
        }

        if ('' === $path) {
            $path = '/'.($pageModel->alias ?: $pageModel->id);
        } elseif (!str_starts_with($path, '/')) {
            $path = '/'.($pageModel->alias ?: $pageModel->id).'/'.$path;
        }

        parent::__construct(
            $path,
            $defaults,
            $requirements,
            $options,
            $pageModel->domain,
            $pageModel->rootUseSSL ? 'https' : 'http',
            $methods
        );

        $this->pageModel = $pageModel;
        $this->urlPrefix = $pageModel->urlPrefix;
        $this->urlSuffix = $pageModel->urlSuffix;
    }

    public function getPageModel(): PageModel
    {
        return $this->pageModel;
    }

    public function getPath(): string
    {
        $path = parent::getPath();

        if ('' !== $this->getUrlPrefix()) {
            $path = '/'.$this->getUrlPrefix().$path;
        }

        return $path.$this->getUrlSuffix();
    }

    public function getOriginalPath(): string
    {
        return parent::getPath();
    }

    public function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    public function setUrlPrefix(string $urlPrefix): self
    {
        $this->urlPrefix = $urlPrefix;

        return $this;
    }

    public function getUrlSuffix(): string
    {
        return $this->urlSuffix;
    }

    public function setUrlSuffix(string $urlSuffix): self
    {
        $this->urlSuffix = $urlSuffix;

        return $this;
    }

    /**
     * Sets the object this URL points to.
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): object|null
    {
        return $this->content;
    }

    public function getRouteKey(): string
    {
        return 'tl_page.'.$this->pageModel->id;
    }
}
