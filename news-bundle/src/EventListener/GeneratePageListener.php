<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Environment;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\NewsFeedModel;
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
     * @param PageModel          $pageModel
     * @param LayoutModel|object $layoutModel
     */
    public function onGeneratePage(PageModel $pageModel, LayoutModel $layoutModel): void
    {
        $newsfeeds = StringUtil::deserialize($layoutModel->newsfeeds);

        if (empty($newsfeeds) || !\is_array($newsfeeds)) {
            return;
        }

        $this->framework->initialize();

        /** @var NewsFeedModel $adapter */
        $adapter = $this->framework->getAdapter(NewsFeedModel::class);

        if (!($feeds = $adapter->findByIds($newsfeeds)) instanceof Collection) {
            return;
        }

        $this->addFeedMarkupToPageHeader($feeds);
    }

    /**
     * Adds the feed markup to the page header.
     *
     * @param Collection|NewsFeedModel[] $feeds
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
