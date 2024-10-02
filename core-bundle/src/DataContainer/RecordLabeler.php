<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RecordLabeler
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function getLabel(string $identifier, array $data): string
    {
        $event = new DataContainerRecordLabelEvent($identifier, $data);

        $this->eventDispatcher->dispatch($event);

        return $event->getLabel() ?? $identifier;
    }
}
