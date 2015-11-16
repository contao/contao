<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsModel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds a query to the front end preview URL.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
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
     * Constructor.
     *
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
    public function onPreviewUrlCreate(PreviewUrlCreateEvent $event)
    {
        if (!$this->framework->isInitialized() || 'news' !== $event->getKey()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // Return on the news archive list page
        if ('tl_news' === $request->query->get('table') && !$request->query->has('act')) {
            return;
        }

        $id = $event->getId();

        // Overwrite the ID if the news settings are edited
        if ('tl_news' === $request->query->get('table') && 'edit' === $request->query->get('act')) {
            $id = $request->query->get('id');
        }

        /** @var NewsModel $newsModel */
        $newsModel = $this->framework->getAdapter('Contao\NewsModel');

        if (null === ($news = $newsModel->findByPk($id))) {
            return;
        }

        $event->setQuery('news=' . $news->id);
    }
}
