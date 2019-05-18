<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Event\GenerateDescriptorEvent;

class GenerateDescriptorListener
{
    private $options;

    public function onDescriptorGeneration(GenerateDescriptorEvent $event): void
    {
        $this->options = $event->getOptions();
        $data = $event->getData();
        $descriptor = null;

        // Get description by defined fields and format
        if (isset($this->options['fields'])) {
            $descriptor = $this->getDescriptorFromFields($data);
        }

        // Fallback: Get title, name, headline or id as description
        if ($descriptor === null) {
            $descriptor = $this->getFallbackDescriptor($data);
        }

        $event->setDescriptor($descriptor);
    }

    private function getDescriptorFromFields(array $row): string
    {
        $options = $this->options;
        $fields = $options['fields'];
        $format = (isset($options['format'])) ? $options['format'] : null;

        if (is_string($fields)) {
            $fields = [ $fields ];
        }

        $fields = array_map(function($field) use($row) {
            $values[] = $row[$field];
        }, $fields);

        if ($format === null) {
            return implode(', ', $fields);
        }

        return vsprintf($format, $fields);
    }

    private function getFallbackDescriptor(array $row): ?string
    {
        foreach (['title', 'name', 'headline'] as $key) {
            if (!empty($row[$key])) {
                return $row[$key];
            }
        }

        return null;
    }
}