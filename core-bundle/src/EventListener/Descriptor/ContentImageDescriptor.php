<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\Descriptor;

use Contao\CoreBundle\Event\GenerateDescriptorEvent;
use Contao\FilesModel;
use Contao\StringUtil;

class ContentImageDescriptor
{
    public function onDescriptorGeneration(GenerateDescriptorEvent $event)
    {
        $data = $event->getData();

        if ($data['type'] !== 'image') {
            return;
        }

        $uuid = StringUtil::binToUuid($data['singleSRC']);
        $image = FilesModel::findByUuid($uuid);

        if ($image !== null) {
            $event->setDescriptor($image->path);
        } else {
            $event->setDescriptor($uuid);
        }
    }
}