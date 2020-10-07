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

class UndoDescriptionListener
{
    /**
     * @var array
     */
    private $options;

    public function __invoke(UndoDescriptionEvent $event): void
    {
        $this->options = $event->getOptions();
        $row = $event->getData();
        $description = null;

        // Get description by defined fields and format
        if (isset($this->options['fields'])) {
            $description = $this->getDescriptionFromFields($row);
        }

        // Fallback: Check for some often used fields
        if (null === $description) {
            $description = $this->getFallbackDescription($row);
        }

        // Fallback: If everything else failed, we fall back to the row ID
        if (null === $description) {
            $description = 'ID ' . (string) $row['id'];
        }

        $event->setDescription($description);
    }

    private function getDescriptionFromFields(array $row): string
    {
        $options = $this->options;
        $fields = $options['fields'];
        $format = $options['format'] ?? null;

        if (\is_string($fields)) {
            $fields = [$fields];
        }

        $values = array_map(
            static function ($field) use ($row) {
                return $row[$field] ?? '';
            },
            $fields
        );

        if (null === $format) {
            return implode(', ', $values);
        }

        return vsprintf($format, $values);
    }

    private function getFallbackDescription(array $row): ?string
    {
        foreach (['title', 'username', 'email', 'name', 'headline'] as $key) {
            if (!empty($row[$key])) {
                return $row[$key];
            }
        }

        return null;
    }
}
