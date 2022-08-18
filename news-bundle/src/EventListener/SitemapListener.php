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

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
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
    }

    public function __invoke(SitemapEvent $event): void
    {
        $arrRoot = $this->framework->createInstance(Database::class)->getChildRecords($event->getRootPageIds(), 'tl_page');

        // Early return here in the unlikely case that there are no pages
        if (empty($arrRoot)) {
            return;
        }

        $arrPages = [];
        $time = time();

        // Get all news archives
        $objArchives = $this->framework->getAdapter(NewsArchiveModel::class)->findByProtected('');

        if (null === $objArchives) {
            return;
        }

        // Walk through each news archive
        foreach ($objArchives as $objArchive) {
            // Skip news archives without target page
            if (!$objArchive->jumpTo) {
                continue;
            }

            // Skip news archives outside the root nodes
            if (!\in_array($objArchive->jumpTo, $arrRoot, true)) {
                continue;
            }

            $objParent = $this->framework->getAdapter(PageModel::class)->findWithDetails($objArchive->jumpTo);

            // The target page does not exist
            if (null === $objParent) {
                continue;
            }

            // The target page has not been published (see #5520)
            if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time)) {
                continue;
            }

            // The target page is protected (see #8416)
            if ($objParent->protected) {
                continue;
            }

            // The target page is exempt from the sitemap (see #6418)
            if ('noindex,nofollow' === $objParent->robots) {
                continue;
            }

            // Get the items
            $objArticles = $this->framework->getAdapter(NewsModel::class)->findPublishedDefaultByPid($objArchive->id);

            if (null === $objArticles) {
                continue;
            }

            foreach ($objArticles as $objNews) {
                if ('noindex,nofollow' === $objNews->robots) {
                    continue;
                }

                $arrPages[] = $objParent->getAbsoluteUrl('/'.($objNews->alias ?: $objNews->id));
            }
        }

        foreach ($arrPages as $strUrl) {
            $event->addUrlToDefaultUrlSet($strUrl);
        }
    }
}
