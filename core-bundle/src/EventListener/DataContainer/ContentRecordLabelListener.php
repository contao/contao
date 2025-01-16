<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener]
class ContentRecordLabelListener
{
    public function __construct(private readonly TranslatorInterface&TranslatorBagInterface $translator)
    {
    }

    public function __invoke(DataContainerRecordLabelEvent $event): void
    {
        if (
            !str_starts_with($event->getIdentifier(), 'contao.db.tl_content.')
            || !isset($event->getData()['type'])
        ) {
            return;
        }

        $type = $event->getData()['type'];
        $labelKey = "CTE.$type.0";

        if ($this->translator->getCatalogue()->has($labelKey, 'contao_default')) {
            $label = $this->translator->trans($labelKey, [], 'contao_default');
        } else {
            $label = $type;
        }

        $event->setLabel($label);
    }
}
