<?php

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
     * The referenced content object.
     *
     * @var object|null
     */
    protected $content;

    public function __construct(PageModel $page, string $parameters = '', $content = null)
    {
        $page->loadDetails();

        $defaults = [
            '_token_check' => true,
            '_controller' => 'Contao\FrontendIndex::renderPage',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
            '_locale' => $page->rootLanguage,
            'pageModel' => $page,
        ];

        $requirements = [];
        $path = sprintf('/%s%s', $page->alias, $page->urlSuffix);

        if (!$page->parameters) {
            $requirements = ['parameters' => $page->requireItem ? '/.+' : '(/.+)?'];
            $defaults['parameters'] = $parameters;
            $path = sprintf('/%s{parameters}%s', $page->alias, $page->urlSuffix);
        }

        if ('' !== $page->languagePrefix) {
            $path = '/'.$page->languagePrefix.$path;
        }

        parent::__construct(
            $path,
            $defaults,
            $requirements,
            ['utf8' => true],
            $page->domain,
            $page->rootUseSSL ? 'https' : null
        );

        $this->page = $page;

        if (null !== $content) {
            $this->setContent($content);
        }
    }

    public function getRouteKey()
    {
        return 'tl_page.'.$this->page->id;
    }

    /**
     * Set the object this url points to.
     *
     * @param mixed $object
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

    public function getPage(): PageModel
    {
        return $this->page;
    }
}
