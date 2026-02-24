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
use Contao\CoreBundle\Event\RenderPageEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds the feeds to the page header.
 *
 * @internal
 */
#[AsEventListener]
class AddFeedsFromLayoutListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(RenderPageEvent $event): void
    {
        if (
            !($layout = $event->getLayout())
            || !$event->getResponseContext()->has(HtmlHeadBag::class)
        ) {
            return;
        }

        $this->framework->initialize();

        if (!$calendarfeeds = StringUtil::deserialize($layout->calendarfeeds, true)) {
            return;
        }

        $adapter = $this->framework->getAdapter(PageModel::class);

        if (!$feeds = $adapter->findMultipleByIds($calendarfeeds)) {
            return;
        }

        $headBag = $event->getResponseContext()->get(HtmlHeadBag::class);

        foreach ($feeds as $feed) {
            if (CalendarFeedController::TYPE !== $feed->type) {
                continue;
            }

            try {
                $href = $this->urlGenerator->generate($feed, [], UrlGeneratorInterface::ABSOLUTE_URL);
            } catch (ExceptionInterface) {
                continue;
            }

            $headBag->addLinkTag((new HtmlAttributes())
                ->set('type', $feed->feedFormat)
                ->set('rel', 'alternate')
                ->set('href', $href)
                ->set('title', $feed->title),
            );
        }
    }
}
