<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\Descriptor;

use Contao\CoreBundle\Event\DescriptorGenerationEvent;
use Contao\FilesModel;
use Contao\StringUtil;

class ContentDescriptorGenerationListener
{
    public function onDescriptorGeneration(DescriptorGenerationEvent $event): void
    {
        if ($event->getTable() !== 'tl_content') {
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

    /**
     * @param array $data
     * @return string
     */
    private function getMethodFromType(string $type): string
    {
        return 'get' . ucwords($type) . 'Descriptor';
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

        return ($image !== null) ? $image->name . '.' . $image->extension : $uuid;
    }

    private function getGalleryDescriptor(array $data): string
    {
        $uuids = array_map(function ($uuid) {
            return \StringUtil::binToUuid($uuid);
        }, StringUtil::deserialize($data['multiSRC']));

        $files = FilesModel::findMultipleByUuids($uuids);

        if ($files === null) {
            return implode(', ', $uuids);
        }

        $fileNames = array_reduce($files->fetchAll(), function ($acc, $file) {
            array_push($acc, $file['name']);
            return $acc;
        }, []);

        return implode(', ', $fileNames);
    }
}
