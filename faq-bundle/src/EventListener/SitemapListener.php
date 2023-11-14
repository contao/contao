<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Symfony\Bundle\SecurityBundle\Security;

class SitemapListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
    ) {
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

        // Get all categories
        $objFaqs = $this->framework->getAdapter(FaqCategoryModel::class)->findAll();

        if (null === $objFaqs) {
            return;
        }

        // Walk through each category
        foreach ($objFaqs as $objFaq) {
            // Skip FAQs without target page
            if (!$objFaq->jumpTo) {
                continue;
            }

            // Skip FAQs outside the root nodes
            if (!\in_array($objFaq->jumpTo, $arrRoot, true)) {
                continue;
            }

            $objParent = $this->framework->getAdapter(PageModel::class)->findWithDetails($objFaq->jumpTo);

            // The target page does not exist
            if (null === $objParent) {
                continue;
            }

            // The target page has not been published (see #5520)
            if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time)) {
                continue;
            }

            // The target page is protected (see #8416)
            if ($objParent->protected && !$this->security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objParent->groups)) {
                continue;
            }

            // The target page is exempt from the sitemap (see #6418)
            if ('noindex,nofollow' === $objParent->robots) {
                continue;
            }

            // Get the items
            $objItems = $this->framework->getAdapter(FaqModel::class)->findPublishedByPid($objFaq->id);

            if (null === $objItems) {
                continue;
            }

            foreach ($objItems as $objItem) {
                if ('noindex,nofollow' === $objItem->robots) {
                    continue;
                }

                // Generate the URL
                $arrPages[] = $objParent->getAbsoluteUrl('/'.($objItem->alias ?: $objItem->id));
            }
        }

        foreach ($arrPages as $strUrl) {
            $event->addUrlToDefaultUrlSet($strUrl);
        }
    }
}
