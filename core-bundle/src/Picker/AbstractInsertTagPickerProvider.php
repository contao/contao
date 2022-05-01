<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

abstract class AbstractInsertTagPickerProvider extends AbstractPickerProvider
{
    /**
     * Returns the configured insert tag or the default one.
     */
    protected function getInsertTag(PickerConfig $config): string
    {
        if ($insertTag = $config->getExtraForProvider('insertTag', $this->getName())) {
            return (string) $insertTag;
        }

        return $this->getDefaultInsertTag();
    }

    /**
     * Returns the default insert tag.
     */
    abstract protected function getDefaultInsertTag(): string;

    /**
     * Splits an insert tag at the placeholder (%s) and returns the chunks.
     */
    protected function getInsertTagChunks(PickerConfig $config): array
    {
        return explode('%s', $this->getInsertTag($config), 2);
    }

    /**
     * Returns the value without the surrounding insert tag chunks.
     */
    protected function getInsertTagValue(PickerConfig $config): string
    {
        return explode('|', str_replace($this->getInsertTagChunks($config), '', $config->getValue()), 2)[0];
    }

    /**
     * Returns the insert tag flags.
     */
    protected function getInsertTagFlags(PickerConfig $config): array
    {
        $flags = explode('|', str_replace($this->getInsertTagChunks($config), '', $config->getValue()));

        array_shift($flags);

        return $flags;
    }

    /**
     * Checks if the value matches the insert tag.
     */
    protected function isMatchingInsertTag(PickerConfig $config): bool
    {
        return str_contains($config->getValue(), (string) $this->getInsertTagChunks($config)[0]);
    }
}
