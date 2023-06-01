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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\LayoutModel;
use Contao\NewsBundle\Controller\Page\NewsFeedController;
use Contao\PageModel;
use Contao\StringUtil;

/**
 * @internal
 */
class GeneratePageListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * Adds the feeds to the page header.
     */
    public function __invoke(PageModel $pageModel, LayoutModel $layoutModel): void
    {
        $newsfeeds = StringUtil::deserialize($layoutModel->newsfeeds);

        if (empty($newsfeeds) || !\is_array($newsfeeds)) {
            return;
        }

        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(PageModel::class);

        if (!$feeds = $adapter->findMultipleByIds($newsfeeds)) {
            return;
        }

        foreach ($feeds as $feed) {
            if (NewsFeedController::TYPE !== $feed->type) {
                continue;
            }

            // TODO: Use ResponseContext, once it supports appending to <head>
            $GLOBALS['TL_HEAD'][] = $this->generateFeedTag($feed->getAbsoluteUrl(), $feed->feedFormat, $feed->title);
        }
    }

    private function generateFeedTag(string $href, string $format, string $title): string
    {
        return sprintf('<link type="%s" rel="alternate" href="%s" title="%s">', NewsFeedController::$contentTypes[$format], $href, StringUtil::specialchars($title));
    }
}
