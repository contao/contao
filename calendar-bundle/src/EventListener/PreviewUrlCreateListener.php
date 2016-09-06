<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * @param RequestStack             $requestStack
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(RequestStack $requestStack, ContaoFrameworkInterface $framework)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    /**
     * Adds a query to the front end preview URL.
     *
     * @param PreviewUrlCreateEvent $event
     */
    public function onPreviewUrlCreate(PreviewUrlCreateEvent $event)
    {
        if (!$this->framework->isInitialized() || 'calendar' !== $event->getKey()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // Return on the calendar list page
        if ('tl_calendar_events' === $request->query->get('table') && !$request->query->has('act')) {
            return;
        }

        if (null === ($eventModel = $this->getEventModel($this->getId($event, $request)))) {
            return;
        }

        $event->setQuery('calendar='.$eventModel->id);
    }

    /**
     * Returns the ID.
     *
     * @param PreviewUrlCreateEvent $event
     * @param Request               $request
     *
     * @return int|string
     */
    private function getId(PreviewUrlCreateEvent $event, Request $request)
    {
        // Overwrite the ID if the event settings are edited
        if ('tl_calendar_events' === $request->query->get('table') && 'edit' === $request->query->get('act')) {
            return $request->query->get('id');
        }

        return $event->getId();
    }

    /**
     * Returns the event model.
     *
     * @param int $id The ID
     *
     * @return CalendarEventsModel|null
     */
    private function getEventModel($id)
    {
        /** @var CalendarEventsModel $adapter */
        $adapter = $this->framework->getAdapter('Contao\CalendarEventsModel');

        return $adapter->findByPk($id);
    }
}
