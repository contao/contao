<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarBundle\Controller\Page\CalendarFeedController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsHook('generatePage')]
class GeneratePageListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * Adds the feeds to the page header.
     */
    public function __invoke(PageModel $pageModel, LayoutModel $layoutModel): void
    {
        $calendarfeeds = StringUtil::deserialize($layoutModel->calendarfeeds);

        if (empty($calendarfeeds) || !\is_array($calendarfeeds)) {
            return;
        }

        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(PageModel::class);

        if (!$feeds = $adapter->findMultipleByIds($calendarfeeds)) {
            return;
        }

        foreach ($feeds as $feed) {
            if (CalendarFeedController::TYPE !== $feed->type) {
                continue;
            }

            try {
                // TODO: Use ResponseContext, once it supports appending to <head>
                $GLOBALS['TL_HEAD'][] = $this->generateFeedTag($this->urlGenerator->generate($feed, [], UrlGeneratorInterface::ABSOLUTE_URL), $feed->feedFormat, $feed->title);
            } catch (ExceptionInterface) {
            }
        }
    }

    private function generateFeedTag(string $href, string $format, string $title): string
    {
        return \sprintf('<link type="%s" rel="alternate" href="%s" title="%s">', CalendarFeedController::$contentTypes[$format], $href, StringUtil::specialchars($title));
    }
}
