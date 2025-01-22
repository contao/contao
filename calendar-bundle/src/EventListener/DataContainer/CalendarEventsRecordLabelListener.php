<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener\DataContainer;

use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener]
class CalendarEventsRecordLabelListener
{
    public function __construct(private readonly TranslatorInterface&TranslatorBagInterface $translator)
    {
    }

    public function __invoke(DataContainerRecordLabelEvent $event): void
    {
        if (!str_starts_with($event->getIdentifier(), 'contao.db.tl_calendar_events.')) {
            return;
        }

        $label = $event->getData()['title'] ?? null;

        if (!$label) {
            $label = $this->translator->trans('tl_calendar_events.record_label', [$event->getData()['id'] ?? ''], 'contao_tl_calendar_events');
        }

        $event->setLabel($label);
    }
}
