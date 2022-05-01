<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;

/**
 * @internal
 */
class SitemapListener
{
    public function __construct(private ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(SitemapEvent $sitemapEvent): void
    {
        $this->framework->initialize();

        /** @var NewsArchiveModel $newsArchiveModel */
        $newsArchiveModelAdapter = $this->framework->getAdapter(NewsArchiveModel::class);

        // Get all news archives
        $archives = $newsArchiveModelAdapter->findAll();

        if (null === $archives) {
            return;
        }

        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        /** @var NewsModel $newsModelAdapter */
        $newsModelAdapter = $this->framework->getAdapter(NewsModel::class);

        /** @var News $newsAdapter */
        $newsAdapter = $this->framework->getAdapter(News::class);

        $rootPageIds = array_map('intval', $sitemapEvent->getRootPageIds());

        /** @var NewsArchiveModel $archive */
        foreach ($archives as $archive) {
            // Skip archives without a target page
            if (empty($archive->jumpTo)) {
                continue;
            }

            // Skip archive if protected and not accessible
            if (!$controllerAdapter->isVisibleElement($archive)) {
                continue;
            }

            $targetPage = $pageModelAdapter->findPublishedById($archive->jumpTo);

            // Skip unpublished pages
            if (null === $targetPage) {
                continue;
            }

            $targetPage->loadDetails();

            // Skip news outside the current page root IDs
            if (!\in_array((int) $targetPage->rootId, $rootPageIds, true)) {
                continue;
            }

            // Skip target page if protected and cannot be accessed
            if (!$controllerAdapter->isVisibleElement($targetPage)) {
                continue;
            }

            // The target page is exempt from the sitemap (see #6418)
            if ('noindex,nofollow' === $targetPage->robots) {
                continue;
            }

            // Get the news items
            $articles = $newsModelAdapter->findPublishedDefaultByPid((int) $archive->id);

            if (null === $articles) {
                continue;
            }

            foreach ($articles as $article) {
                // The news article is exempt from the sitemap
                if ('noindex,nofollow' === $article->robots) {
                    continue;
                }

                // Add URL to the sitemap
                $sitemapEvent->addUrlToDefaultUrlSet($newsAdapter->generateNewsUrl($article, false, true));
            }
        }
    }
}
