<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\LayoutModel;
use Contao\NewsFeedModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\Template;

class GeneratePageListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param PageModel   $objPage
     * @param LayoutModel $objLayout
     * @param PageRegular $objPageRegular
     */
    public function onGeneratePage($objPage, $objLayout, $objPageRegular)
    {
        $newsfeeds = deserialize($objLayout->newsfeeds);

        if (empty($newsfeeds) || !is_array($newsfeeds)) {
            return;
        }

        $this->framework->initialize();

        /** @var NewsFeedModel[] $feeds */
        $feeds = $this->framework->getAdapter('Contao\NewsFeedModel')->findByIds($newsfeeds);

        if (null === $feeds) {
            return;
        }

        /** @var Template $template */
        $template = $this->framework->getAdapter('Contao\Template');
        $base = $this->framework->getAdapter('Contao\Environment')->get('base');

        foreach ($feeds as $feed) {
            $GLOBALS['TL_HEAD'][] = $template->generateFeedTag(
                    ($feed->feedBase ?: $base) . 'share/' . $feed->alias . '.xml',
                    $feed->format,
                    $feed->title
                )
            ;
        }
    }
}
