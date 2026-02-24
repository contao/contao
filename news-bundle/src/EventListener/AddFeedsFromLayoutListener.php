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

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Event\LayoutEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\LayoutModel;
use Contao\NewsBundle\Controller\Page\NewsFeedController;
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
class AddFeedsFromLayoutListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly ResponseContextAccessor $responseContextAccessor,
    ) {
    }

    #[AsHook('generatePage')]
    public function onGeneratePage(PageModel $pageModel, LayoutModel $layout): void
    {
        if (!$responseContext = $this->responseContextAccessor->getResponseContext()) {
            return;
        }

        $this->addFeedsToResponseContext($layout, $responseContext);
    }

    #[AsEventListener]
    public function onLayoutEvent(LayoutEvent $event): void
    {
        if (!($layout = $event->getLayout()) || !($responseContext = $event->getResponseContext())) {
            return;
        }

        $this->addFeedsToResponseContext($layout, $responseContext);
    }

    private function addFeedsToResponseContext(LayoutModel $layout, ResponseContext $responseContext): void
    {
        if (!$responseContext->has(HtmlHeadBag::class)) {
            return;
        }

        $this->framework->initialize();

        if (!$newsfeeds = StringUtil::deserialize($layout->newsfeeds, true)) {
            return;
        }

        $adapter = $this->framework->getAdapter(PageModel::class);

        if (!$feeds = $adapter->findMultipleByIds($newsfeeds)) {
            return;
        }

        $headBag = $responseContext->get(HtmlHeadBag::class);

        foreach ($feeds as $feed) {
            if (NewsFeedController::TYPE !== $feed->type) {
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
