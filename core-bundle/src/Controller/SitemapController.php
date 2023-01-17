<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\ArticleModel;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * @Route(defaults={"_scope" = "frontend"})
 *
 * @internal
 */
class SitemapController extends AbstractController
{
    private PageRegistry $pageRegistry;

    public function __construct(PageRegistry $pageRegistry)
    {
        $this->pageRegistry = $pageRegistry;
    }

    /**
     * @Route("/sitemap.xml")
     */
    public function __invoke(Request $request): Response
    {
        $this->initializeContaoFramework();

        $pageModel = $this->getContaoAdapter(PageModel::class);
        $rootPages = $pageModel->findPublishedRootPages(['dns' => $request->getHost()]);

        if (null === $rootPages) {
            // We did not find root pages by matching host name, let's fetch those that do not have any domain configured
            $rootPages = $pageModel->findPublishedRootPages(['dns' => '']);

            if (null === $rootPages) {
                return new Response('', Response::HTTP_NOT_FOUND);
            }
        }

        $urls = [];
        $rootPageIds = [];
        $tags = ['contao.sitemap'];

        foreach ($rootPages as $rootPage) {
            $pages = $this->getPageAndArticleUrls((int) $rootPage->id);
            $urls[] = $this->callLegacyHook($rootPage, $pages);

            $rootPageIds[] = $rootPage->id;
            $tags[] = 'contao.sitemap.'.$rootPage->id;
        }

        $urls = array_unique(array_merge(...$urls));

        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');

        foreach ($urls as $url) {
            $loc = $sitemap->createElement('loc', $url);
            $urlEl = $sitemap->createElement('url');
            $urlEl->appendChild($loc);
            $urlSet->appendChild($urlEl);
        }

        $sitemap->appendChild($urlSet);

        $this->container
            ->get('event_dispatcher')
            ->dispatch(new SitemapEvent($sitemap, $request, $rootPageIds), ContaoCoreEvents::SITEMAP)
        ;

        // Cache the response for a month in the shared cache and tag it for invalidation purposes
        $response = new Response((string) $sitemap->saveXML(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
        $response->setSharedMaxAge(2592000); // will be unset by the MakeResponsePrivateListener if a user is logged in

        $this->tagResponse($tags);

        return $response;
    }

    private function callLegacyHook(PageModel $rootPage, array $pages): array
    {
        $systemAdapter = $this->getContaoAdapter(System::class);

        // HOOK: take additional pages
        if (isset($GLOBALS['TL_HOOKS']['getSearchablePages']) && \is_array($GLOBALS['TL_HOOKS']['getSearchablePages'])) {
            trigger_deprecation('contao/core-bundle', '4.11', 'Using the "getSearchablePages" hook is deprecated. Use the "contao.sitemap" event instead.');

            foreach ($GLOBALS['TL_HOOKS']['getSearchablePages'] as $callback) {
                $pages = $systemAdapter->importStatic($callback[0])->{$callback[1]}($pages, $rootPage->id, true, $rootPage->language);
            }
        }

        return $pages;
    }

    private function getPageAndArticleUrls(int $parentPageId): array
    {
        $pageModelAdapter = $this->getContaoAdapter(PageModel::class);

        // Since the publication status of a page is not inherited by its child
        // pages, we have to use findByPid() instead of findPublishedByPid() and
        // filter out unpublished pages in the foreach loop (see #2217)
        $pageModels = $pageModelAdapter->findByPid($parentPageId, ['order' => 'sorting']);

        if (null === $pageModels) {
            return [];
        }

        $articleModelAdapter = $this->getContaoAdapter(ArticleModel::class);

        $result = [];

        // Recursively walk through all subpages
        foreach ($pageModels as $pageModel) {
            // Load details in order to inherit permission settings (see #5556)
            $pageModel->loadDetails();

            if ($pageModel->protected && !$this->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $pageModel->groups)) {
                continue;
            }

            $isPublished = $pageModel->published && (!$pageModel->start || $pageModel->start <= time()) && (!$pageModel->stop || $pageModel->stop > time());

            if (
                $isPublished
                && !$pageModel->requireItem
                && 'noindex,nofollow' !== $pageModel->robots
                && $this->pageRegistry->supportsContentComposition($pageModel)
                && $this->pageRegistry->isRoutable($pageModel)
                && 'html' === $this->pageRegistry->getRoute($pageModel)->getDefault('_format')
            ) {
                try {
                    $urls = [$pageModel->getAbsoluteUrl()];

                    // Get articles with teaser
                    if (null !== ($articleModels = $articleModelAdapter->findPublishedWithTeaserByPid($pageModel->id, ['ignoreFePreview' => true]))) {
                        foreach ($articleModels as $articleModel) {
                            $urls[] = $pageModel->getAbsoluteUrl('/articles/'.($articleModel->alias ?: $articleModel->id));
                        }
                    }

                    $result[] = $urls;
                } catch (ExceptionInterface $exception) {
                    // Skip URL for this page but generate child pages
                }
            }

            $result[] = $this->getPageAndArticleUrls((int) $pageModel->id);
        }

        return array_merge(...$result);
    }
}
