<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarFeedModel;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Environment;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;

class GeneratePageListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Adds the feeds to the page header.
     *
     * @param PageModel          $objPage
     * @param LayoutModel|object $objLayout
     */
    public function onGeneratePage(PageModel $objPage, LayoutModel $objLayout): void
    {
        $calendarfeeds = StringUtil::deserialize($objLayout->calendarfeeds);

        if (empty($calendarfeeds) || !is_array($calendarfeeds)) {
            return;
        }

        $this->framework->initialize();

        /** @var CalendarFeedModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarFeedModel::class);

        if (!($feeds = $adapter->findByIds($calendarfeeds)) instanceof Collection) {
            return;
        }

        $this->addFeedMarkupToPageHeader($feeds);
    }

    /**
     * Adds the feed markup to the page header.
     *
     * @param Collection|CalendarFeedModel[] $feeds
     */
    private function addFeedMarkupToPageHeader(Collection $feeds): void
    {
        /** @var Template $template */
        $template = $this->framework->getAdapter(Template::class);

        /** @var Environment $environment */
        $environment = $this->framework->getAdapter(Environment::class);

        foreach ($feeds as $feed) {
            $GLOBALS['TL_HEAD'][] = $template->generateFeedTag(
                sprintf('%sshare/%s.xml', ($feed->feedBase ?: $environment->get('base')), $feed->alias),
                $feed->format,
                $feed->title
            );
        }
    }
}
