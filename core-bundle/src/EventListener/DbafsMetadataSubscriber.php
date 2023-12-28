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

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Filesystem\Dbafs\AbstractDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\Dbafs\RetrieveDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\Image\ImportantPart;
use Contao\StringUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class DbafsMetadataSubscriber implements EventSubscriberInterface
{
    public function enhanceMetadata(RetrieveDbafsMetadataEvent $event): void
    {
        if (!$this->supports($event)) {
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
            $metadata[$lang] = new Metadata(array_merge([Metadata::VALUE_UUID => $event->getUuid()->toRfc4122()], $data));
        }

        $event->set('metadata', $metadata);
    }

    public function normalizeMetadata(StoreDbafsMetadataEvent $event): void
    {
        if (!$this->supports($event)) {
            return;
        }

        $extraMetadata = $event->getExtraMetadata();
        $importantPart = $extraMetadata['importantPart'] ?? null;

        if ($importantPart instanceof ImportantPart) {
            $event->set('importantPartX', $importantPart->getX());
            $event->set('importantPartY', $importantPart->getY());
            $event->set('importantPartWidth', $importantPart->getWidth());
            $event->set('importantPartHeight', $importantPart->getHeight());
        }

        if (\is_array($data = $extraMetadata['metadata'] ?? null)) {
            $metadata = array_map(
                static function (Metadata $metadata) use ($event): array {
                    if (null !== ($uuid = $metadata->getUuid()) && $uuid !== ($recordUuid = $event->getUuid()->toRfc4122())) {
                        throw new \LogicException(sprintf('The UUID stored in the file metadata (%s) does not match the one of the record (%s).', $uuid, $recordUuid));
                    }

                    return array_diff_key($metadata->all(), [Metadata::VALUE_UUID => null]);
                },
                $data
            );

            $event->set('meta', serialize($metadata));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RetrieveDbafsMetadataEvent::class => ['enhanceMetadata'],
            StoreDbafsMetadataEvent::class => ['normalizeMetadata'],
        ];
    }

    private function supports(AbstractDbafsMetadataEvent $event): bool
    {
        return 'tl_files' === $event->getTable();
    }
}
