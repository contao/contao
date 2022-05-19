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

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Events;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class PreviewUrlConvertListener
{
    public function __construct(private ContaoFramework $framework)
    {
    }

    /**
     * Adds the front end preview URL to the event.
     */
    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        if (null === ($eventModel = $this->getEventModel($event->getRequest()))) {
            return;
        }

        $event->setUrl($this->framework->getAdapter(Events::class)->generateEventUrl($eventModel, true));
    }

    private function getEventModel(Request $request): CalendarEventsModel|null
    {
        if (!$request->query->has('calendar')) {
            return null;
        }

        return $this->framework->getAdapter(CalendarEventsModel::class)->findByPk($request->query->get('calendar'));
    }
}
