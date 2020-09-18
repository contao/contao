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

use Contao\CoreBundle\Event\UndoDescriptionEvent;

class UndoDescriptionListener
{
    public function onGenerateDescription(UndoDescriptionEvent $event): void
    {
    }
}
