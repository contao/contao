<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\ArticleModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentUriGenerator;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * @internal
 */
class PreviewUrlConvertListener
{
    public function __construct(
        private ContaoFramework $framework,
        private PageRegistry $pageRegistry,
        private UriSigner $signer,
        private string $fragmentPath = '/_fragment',
    ) {
    }

    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->query->has('url')) {
            $event->setUrl($request->getBaseUrl().'/'.$request->query->get('url'));

            return;
        }

        if ($request->query->has('page')) {
            $pageAdapter = $this->framework->getAdapter(PageModel::class);

            if (!$page = $pageAdapter->findWithDetails($request->query->get('page'))) {
                return;
            }

            try {
                $event->setUrl($page->getPreviewUrl($this->getParams($request)));
            } catch (ExceptionInterface) {
                $event->setUrl($this->getFragmentUrl($request, $page));
            }
        }
    }

    private function getParams(Request $request): string|null
    {
        if (!$request->query->has('article')) {
            return null;
        }

        $articleAdapter = $this->framework->getAdapter(ArticleModel::class);

        if (!$article = $articleAdapter->findByAlias($request->query->get('article'))) {
            return null;
        }

        // Add the /article/ fragment (see contao/core-bundle#673)
        return sprintf('/articles/%s%s', 'main' !== $article->inColumn ? $article->inColumn.':' : '', $article->alias);
    }

    private function getFragmentUrl(Request $request, PageModel $pageModel): string
    {
        $route = $this->pageRegistry->getRoute($pageModel);

        $defaults = $route->getDefaults();
        $defaults['pageModel'] = $pageModel->id;

        $uri = new ControllerReference($defaults['_controller'], $defaults);

        return (new FragmentUriGenerator($this->fragmentPath, $this->signer))->generate($uri, $request, true);
    }
}
