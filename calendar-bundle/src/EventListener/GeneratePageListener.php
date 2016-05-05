<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarFeedModel;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\LayoutModel;
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
        $calendarfeeds = deserialize($objLayout->calendarfeeds);

        if (empty($calendarfeeds) || !is_array($calendarfeeds)) {
            return;
        }

        $this->framework->initialize();

        /** @var CalendarFeedModel[] $feeds */
        $feeds = $this->framework->getAdapter('Contao\CalendarFeedModel')->findByIds($calendarfeeds);

        if (null === $feeds) {
            return;
        }

        /** @var Template $template */
        $template = $this->framework->getAdapter('Contao\Template');
        $base = $this->framework->getAdapter('Contao\Environment')->get('base');

        foreach ($feeds as $feed) {
            $GLOBALS['TL_HEAD'][] = $template->generateFeedTag(
                    ($feed->feedBase ?: $base).'share/'.$feed->alias.'.xml',
                    $feed->format,
                    $feed->title
                )
            ;
        }
    }
}
