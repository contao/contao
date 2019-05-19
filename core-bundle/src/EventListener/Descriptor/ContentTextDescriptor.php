<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\Descriptor;

use Contao\CoreBundle\Event\GenerateDescriptorEvent;

class ContentTextDescriptor
{
    public function onDescriptorGeneration(GenerateDescriptorEvent $event)
    {
        $data = $event->getData();

        if ($data['type'] !== 'text') {
            return;
        }

        $event->setDescriptor(strip_tags($data['text']));
    }
}