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
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Security\AccessTokenHandler;
use Contao\PageModel;
use League\Uri\Modifier;
use League\Uri\Uri;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentUriGenerator;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * @internal
 */
class PreviewUrlConvertListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PageRegistry $pageRegistry,
        private readonly UriSigner $signer,
        private readonly Security $security,
        private readonly AccessTokenHandler $accessTokenHandler,
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

        if ($request->query->has('page')) {
            $pageAdapter = $this->framework->getAdapter(PageModel::class);

            if (!$page = $pageAdapter->findWithDetails($request->query->get('page'))) {
                return;
            }

            try {
                $event->setUrl($page->getAbsoluteUrl($this->getParams($request, $page->id)));
            } catch (RouteParametersException $e) {
                $route = $e->getRoute();

                if (!$route instanceof PageRoute || !$route->getPageModel()->requireItem) {
                    $event->setUrl($this->getFragmentUrl($request, $page));
                }

                // Ignore the exception and set no URL for pages with requireItem (see #3525)
            } catch (ExceptionInterface) {
                $event->setUrl($this->getFragmentUrl($request, $page));
            }

            $origin = Uri::new($request->getUri());
            $target = Uri::new($event->getUrl());
            $token = $this->security->getToken();

            if ($token && $origin->getHost() !== $target->getHost()) {
                $accessToken = $this->accessTokenHandler->createTokenForUser($token->getUserIdentifier());
                $preAuthTarget = Modifier::from((string) $origin->withHost($target->getHost()))->appendQueryParameters(['access_token' => $accessToken]);

                $event->setResponse(new RedirectResponse((string) $preAuthTarget));
            }
        }
    }

    private function getParams(Request $request, int $pageId): string|null
    {
        if (!$request->query->has('article')) {
            return null;
        }

        $articleAdapter = $this->framework->getAdapter(ArticleModel::class);

        if (!$article = $articleAdapter->findByIdOrAliasAndPid($request->query->get('article'), $pageId)) {
            return null;
        }

        // Add the /article/ fragment (see contao/core-bundle#673)
        return '/articles/'.($article->alias ?: $article->id);
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
