<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Event\DbafsMetadataEvent;
use Contao\CoreBundle\File\Metadata;
use Contao\Image\ImportantPart;
use Contao\StringUtil;

/**
 * @internal
 */
class DbafsMetadataListener
{
    public function __invoke(DbafsMetadataEvent $event): void
    {
        if ('tl_files' !== $event->getTable()) {
            return;
        }

        $row = $event->getRow();

        // Add important part
        if (
            null !== ($x = $row['importantPartX'] ?? null)
            && null !== ($y = $row['importantPartY'] ?? null)
            && null !== ($width = $row['importantPartWidth'] ?? null)
            && null !== ($height = $row['importantPartHeight'] ?? null)
        ) {
            $importantPart = new ImportantPart((float) $x, (float) $y, (float) $width, (float) $height);
            $event->set('importantPart', $importantPart);
        }

        // Add file metadata
        $metadata = [];

        foreach (StringUtil::deserialize($row['meta'] ?? null, true) as $lang => $data) {
            $metadata[$lang] = new Metadata(array_merge([Metadata::VALUE_UUID => $event->getUuid()], $data));
        }

        $event->set('metadata', $metadata);
    }
}
