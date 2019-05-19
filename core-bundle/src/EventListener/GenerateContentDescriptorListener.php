<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Event\GenerateDescriptorEvent;

class GenerateContentDescriptorListener
{
    public function onDescriptorGeneration(GenerateDescriptorEvent $event): void
    {
        if ($event->getTable() !== 'tl_content') {
            return;
        }

        $options = $event->getOptions();
        $data = $event->getData();
        $descriptor = null;

        // TODO: Determine best descriptor for each content element

        $event->setDescriptor($descriptor);
    }
}