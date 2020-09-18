<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Undo;

use Contao\CoreBundle\Event\UndoDescriptionEvent;
use Contao\FilesModel;
use Contao\StringUtil;

class ContentUndoDescriptionListener
{
    public function onGenerateDescription(UndoDescriptionEvent $event): void
    {
        if ('tl_content' !== $event->getTable()) {
            return;
        }

        $descriptor = null;
        $data = $event->getData();
        $method = $this->getMethodFromType($data['type']);

        if (method_exists($this, $method)) {
            $descriptor = $this->{$method}($data);
        }

        $event->setDescriptor($descriptor);
    }

    private function getMethodFromType(string $type): string
    {
        return 'get'.ucwords($type).'Descriptor';
    }

    private function getHeadlineDescriptor(array $data): string
    {
        $headline = StringUtil::deserialize($data['headline']);

        return $headline[1];
    }

    private function getTextDescriptor(array $data): string
    {
        $text = $data['text'];

        return StringUtil::substrHtml($text, 100);
    }

    private function getHtmlDescriptor(array $data): string
    {
        return $data['html'];
    }

    private function getListDescriptor(array $data): string
    {
        $items = StringUtil::deserialize($data['listitems']);

        return implode(', ', array_values($items));
    }

    private function getImageDescriptor(array $data): string
    {
        $uuid = StringUtil::binToUuid($data['singleSRC']);
        $image = FilesModel::findByUuid($uuid);

        return null !== $image ? $image->name.'.'.$image->extension : $uuid;
    }

    private function getGalleryDescriptor(array $data): string
    {
        $uuids = array_map(
            static function ($uuid) {
                return \StringUtil::binToUuid($uuid);
            },
            StringUtil::deserialize($data['multiSRC'])
        );

        $files = FilesModel::findMultipleByUuids($uuids);

        if (null === $files) {
            return implode(', ', $uuids);
        }

        $fileNames = array_reduce($files->fetchAll(), static function ($acc, $file) {
            array_push($acc, $file['name']);

            return $acc;
        }, []);

        return implode(', ', $fileNames);
    }
}
