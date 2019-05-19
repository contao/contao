<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\Descriptor;

use Contao\CoreBundle\Event\GenerateDescriptorEvent;

class ContentHeadlineDescriptor
{
    public function onDescriptorGeneration(GenerateDescriptorEvent $event)
    {
        $data = $event->getData();

        if ($data['type'] !== 'headline') {
            return;
        }
        $headline = unserialize($data['headline']);
        $event->setDescriptor($headline[1]);
    }
}