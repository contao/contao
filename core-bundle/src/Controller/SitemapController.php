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
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "frontend"})
 *
 * @internal
 */
class SitemapController extends AbstractController
{
    /**
     * @var PageRegistry
     */
    private $pageRegistry;

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

        /** @var PageModel $pageModel */
        $pageModel = $this->get('contao.framework')->getAdapter(PageModel::class);
        $rootPages = $pageModel->findPublishedRootPages(['dns' => $request->server->get('HTTP_HOST')]);

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

        $this->get('event_dispatcher')->dispatch(new SitemapEvent($sitemap, $request, $rootPageIds), ContaoCoreEvents::SITEMAP);

        // Cache the response for a month in the shared cache and tag it for invalidation purposes
        $response = new Response((string) $sitemap->saveXML(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
        $response->setSharedMaxAge(2592000);

        $this->tagResponse($tags);

        // Do not cache the response if a user is logged in.
        if ($this->getUser() instanceof User) {
            $response->headers->removeCacheControlDirective('s-maxage');
            $response->headers->addCacheControlDirective('no-store');
            $response->setPrivate();
        }

        return $response;
    }

    private function callLegacyHook(PageModel $rootPage, array $pages): array
    {
        /** @var System $systemAdapter */
        $systemAdapter = $this->get('contao.framework')->getAdapter(System::class);

        // HOOK: take additional pages
        if (isset($GLOBALS['TL_HOOKS']['getSearchablePages']) && \is_array($GLOBALS['TL_HOOKS']['getSearchablePages'])) {
            trigger_deprecation('contao/core-bundle', '4.11', 'Using the "getSearchablePages" hook is deprecated. Use the "contao.sitemap" event instead.');

            foreach ($GLOBALS['TL_HOOKS']['getSearchablePages'] as $callback) {
                $pages = $systemAdapter->importStatic($callback[0])->{$callback[1]}($pages, $rootPage->id, true, $rootPage->language);
            }
        }

        return $pages;
    }

    private function getPageAndArticleUrls(int $pid): array
    {
        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);

        /** @var ArticleModel $articleModelAdapter */
        $articleModelAdapter = $this->get('contao.framework')->getAdapter(ArticleModel::class);

        // Since the publication status of a page is not inherited by its child
        // pages, we have to use findByPid() instead of findPublishedByPid() and
        // filter out unpublished pages in the foreach loop (see #2217)
        $objPages = $pageModelAdapter->findByPid($pid, ['order' => 'sorting']);

        if (null === $objPages) {
            return [];
        }

        $arrPages = [];

        // Recursively walk through all subpages
        foreach ($objPages as $objPage) {
            if (
                $objPage->protected
                && !$this->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objPage->groups)
            ) {
                continue;
            }

            $isPublished = ($objPage->published && (!$objPage->start || $objPage->start <= time()) && (!$objPage->stop || $objPage->stop > time()));

            // Searchable and not protected
            if ($isPublished && $this->pageRegistry->supportsContentComposition($objPage) && !$objPage->requireItem) {
                $arrPages[] = $objPage->getAbsoluteUrl();

                // Get articles with teaser
                if (($objArticles = $articleModelAdapter->findPublishedWithTeaserByPid($objPage->id, ['ignoreFePreview' => true])) !== null) {
                    foreach ($objArticles as $objArticle) {
                        $arrPages[] = $objPage->getAbsoluteUrl('/articles/'.($objArticle->alias ?: $objArticle->id));
                    }
                }
            }

            // Get subpages
            if ($arrSubpages = $this->getPageAndArticleUrls((int) $objPage->id)) {
                $arrPages = array_merge($arrPages, $arrSubpages);
            }
        }

        return $arrPages;
    }
}
