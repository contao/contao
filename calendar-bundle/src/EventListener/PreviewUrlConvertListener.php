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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsEventListener]
class PreviewUrlConvertListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * Adds the front end preview URL to the event.
     */
    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        if (!$eventModel = $this->getEventModel($event->getRequest())) {
            return;
        }

        $event->setUrl($this->urlGenerator->generate($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    private function getEventModel(Request $request): CalendarEventsModel|null
    {
        if (!$request->query->has('calendar')) {
            return null;
        }

        return $this->framework->getAdapter(CalendarEventsModel::class)->findById($request->query->get('calendar'));
    }
}
