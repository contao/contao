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

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
class PreviewUrlCreateListener
{
    public function __construct(private RequestStack $requestStack, private ContaoFramework $framework)
    {
    }

    /**
     * Adds the news ID to the front end preview URL.
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || 'news' !== $event->getKey()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        // Return on the news archive list page
        if ('tl_news' === $request->query->get('table') && !$request->query->has('act')) {
            return;
        }

        if ((!$id = $this->getId($event, $request)) || (!$newsModel = $this->getNewsModel($id))) {
            return;
        }

        $event->setQuery('news='.$newsModel->id);
    }

    private function getId(PreviewUrlCreateEvent $event, Request $request): int|string
    {
        // Overwrite the ID if the news settings are edited
        if ('tl_news' === $request->query->get('table') && 'edit' === $request->query->get('act')) {
            return $request->query->get('id');
        }

        return $event->getId();
    }

    private function getNewsModel(int|string $id): NewsModel|null
    {
        return $this->framework->getAdapter(NewsModel::class)->findByPk($id);
    }
}
