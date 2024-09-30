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
use Contao\CoreBundle\Exception\RouteParametersException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentUriGenerator;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsEventListener]
class PreviewUrlConvertListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PageRegistry $pageRegistry,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly UriSigner $signer,
        private readonly string $fragmentPath = '/_fragment',
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

        if ($request->query->has('article')) {
            $articleAdapter = $this->framework->getAdapter(ArticleModel::class);
            $article = $articleAdapter->findByIdOrAliasAndPid($request->query->get('article'), $request->query->getInt('page'));

            if ($article) {
                try {
                    $event->setUrl($this->urlGenerator->generate($article, [], UrlGeneratorInterface::ABSOLUTE_URL));

                    return;
                } catch (ExceptionInterface) {
                }
            }
        }

        if ($request->query->has('page')) {
            $pageAdapter = $this->framework->getAdapter(PageModel::class);

            if (!$page = $pageAdapter->findWithDetails($request->query->get('page'))) {
                return;
            }

            try {
                $event->setUrl($this->urlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL));
            } catch (RouteParametersException $e) {
                $route = $e->getRoute();

                if (!$route instanceof PageRoute || !$route->getPageModel()->requireItem) {
                    $event->setUrl($this->getFragmentUrl($request, $page));
                }

                // Ignore the exception and set no URL for pages with requireItem (see #3525)
            } catch (ExceptionInterface) {
                $event->setUrl($this->getFragmentUrl($request, $page));
            }
        }
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
