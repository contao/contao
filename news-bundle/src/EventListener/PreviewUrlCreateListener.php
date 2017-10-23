<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlCreateListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @param RequestStack             $requestStack The request stack
     * @param ContaoFrameworkInterface $framework    The Contao framework service
     */
    public function __construct(RequestStack $requestStack, ContaoFrameworkInterface $framework)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    /**
     * Adds a query to the front end preview URL.
     *
     * @param PreviewUrlCreateEvent $event The event object
     */
    public function onPreviewUrlCreate(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || 'news' !== $event->getKey()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // Return on the news archive list page
        if ('tl_news' === $request->query->get('table') && !$request->query->has('act')) {
            return;
        }

        if (null === ($newsModel = $this->getNewsModel($this->getId($event, $request)))) {
            return;
        }

        $event->setQuery('news='.$newsModel->id);
    }

    /**
     * Returns the ID.
     *
     * @param PreviewUrlCreateEvent $event   The event object
     * @param Request               $request The request object
     *
     * @return int|string The ID
     */
    private function getId(PreviewUrlCreateEvent $event, Request $request)
    {
        // Overwrite the ID if the news settings are edited
        if ('tl_news' === $request->query->get('table') && 'edit' === $request->query->get('act')) {
            return $request->query->get('id');
        }

        return $event->getId();
    }

    /**
     * Returns the news model.
     *
     * @param int|string $id The ID
     *
     * @return NewsModel|null The news model or null
     */
    private function getNewsModel($id): ?NewsModel
    {
        /** @var NewsModel $adapter */
        $adapter = $this->framework->getAdapter(NewsModel::class);

        return $adapter->findByPk($id);
    }
}
