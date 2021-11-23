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
use Contao\Environment;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\NewsFeedModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;

/**
 * @internal
 */
class GeneratePageListener
{
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
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

        $adapter = $this->framework->getAdapter(NewsFeedModel::class);

        if (!($feeds = $adapter->findByIds($newsfeeds)) instanceof Collection) {
            return;
        }

        $template = $this->framework->getAdapter(Template::class);
        $environment = $this->framework->getAdapter(Environment::class);

        foreach ($feeds as $feed) {
            $GLOBALS['TL_HEAD'][] = $template->generateFeedTag(
                sprintf('%sshare/%s.xml', $feed->feedBase ?: $environment->get('base'), $feed->alias),
                $feed->format,
                $feed->title
            );
        }
    }
}
