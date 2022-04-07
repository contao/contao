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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * @internal
 */
class PreviewUrlConvertListener
{
    private ContaoFramework $framework;
    private PageRegistry $pageRegistry;
    private HttpKernelInterface $httpKernel;

    public function __construct(ContaoFramework $framework, PageRegistry $pageRegistry, HttpKernelInterface $httpKernel)
    {
        $this->framework = $framework;
        $this->pageRegistry = $pageRegistry;
        $this->httpKernel = $httpKernel;
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
                $event->setResponse($this->forward($request, $page));
            }
        }
    }

    private function getParams(Request $request): ?string
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

    private function forward(Request $request, PageModel $pageModel): Response
    {
        $route = $this->pageRegistry->getRoute($pageModel);
        $subRequest = $request->duplicate(null, null, $route->getDefaults());

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
