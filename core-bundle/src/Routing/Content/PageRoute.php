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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

class PageRoute extends Route implements RouteObjectInterface
{
    /**
     * @var PageModel
     */
    private $page;

    /**
     * @var string
     */
    private $languagePrefix;

    /**
     * @var string
     */
    private $urlSuffix;

    /**
     * The referenced content object.
     */
    private $content;

    public function __construct(PageModel $page, $content = null)
    {
        $page->loadDetails();

        $defaults = [
            '_token_check' => true,
            '_controller' => 'Contao\FrontendIndex::renderPage',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
            '_locale' => $page->rootLanguage,
            '_format' => 'html',
            'pageModel' => $page,
        ];

        parent::__construct(
            '/'.$page->alias,
            $defaults,
            [],
            ['utf8' => true],
            $page->domain,
            $page->rootUseSSL ? 'https' : null
        );

        $this->page = $page;
        $this->languagePrefix = $page->languagePrefix;
        $this->urlSuffix = $page->urlSuffix;
        $this->content = $content;
    }

    public function getRouteKey()
    {
        return 'tl_page.'.$this->page->id;
    }

    public function getPage(): PageModel
    {
        return $this->page;
    }

    public function getPath()
    {
        $path = parent::getPath();

        if ('' !== $this->getLanguagePrefix()) {
            $path = '/'.$this->getLanguagePrefix().$path;
        }

        return $path.$this->getUrlSuffix();
    }

    public function getLanguagePrefix(): string
    {
        return $this->languagePrefix;
    }

    public function setLanguagePrefix(string $languagePrefix): self
    {
        $this->languagePrefix = $languagePrefix;

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
     * Set the object this url points to.
     */
    public function setContent($object): self
    {
        $this->content = $object;

        return $this;
    }

    /**
     * @return object|null
     */
    public function getContent()
    {
        return $this->content;
    }

    public static function createWithParameters(PageModel $page, string $parameters = '', $content = null)
    {
        $route = new self($page, $content);

        $route->setPath(sprintf('/%s{parameters}', $page->alias));
        $route->setDefault('parameters', $parameters);
        $route->setRequirement('parameters', $page->requireItem ? '/.+' : '(/.+)?');

        return $route;
    }
}
