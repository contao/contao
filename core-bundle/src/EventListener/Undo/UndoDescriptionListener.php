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

use Contao\ArrayUtil;
use Contao\CoreBundle\Event\UndoDescriptionEvent;

class UndoDescriptionListener
{
    public function __invoke(UndoDescriptionEvent $event): void
    {
        $table = $event->getTable();
        $row = $event->getData();
        $fields = $GLOBALS['TL_DCA'][$table]['list']['undo']['fields'] ?? null;
        $format = $GLOBALS['TL_DCA'][$table]['list']['undo']['format'] ?? null;
        $description = null;

        // Check if field config is of schema $type => $field
        if (ArrayUtil::isAssoc($fields) && isset($GLOBALS['TL_DCA'][$table]['list']['undo']['discriminator'])) {
            $discriminator = $GLOBALS['TL_DCA'][$table]['list']['undo']['discriminator'];
            $fields = $fields[$row[$discriminator]] ?? null;
        }

        // Get description by defined fields and format
        if (!empty($fields)) {
            $description = $this->getDescriptionFromFields($row, $fields, $format);
        }

        // Fallback: Check for some often used fields
        if (null === $description) {
            $description = $this->getFallbackDescription($row);
        }

        // Fallback: If everything else failed, we fall back to the row ID
        if (null === $description) {
            $description = 'ID ' . (string)$row['id'];
        }

        $event->setDescription($description);
    }

    private function getDescriptionFromFields(array $row, $fields, ?string $format = null): string
    {
        if (\is_string($fields)) {
            $fields = [$fields];
        }

        $values = array_map(
            function ($field) use ($row) {
                return $this->getValueFromDca($row[$field], $field) ?? '';
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
        foreach (['title', 'username', 'email', 'name', 'headline'] as $field) {
            if (!empty($row[$field])) {
                return $this->getValueFromDca($row[$field], $field);
            }
        }

        return null;
    }

    private function getValueFromDca(string $value, string $field): string
    {
        return $value;
    }
}
