<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Widget;

use Contao\SelectMenu;
use Contao\StringUtil;

class LanguageDependentModule extends SelectMenu
{
    public function generate(): string
    {
        $fields = [];
        $label = $this->strLabel;
        $cssClasses = 'tl_select tl_chosen';

        /** @var array $languages */
        $languages = $this->languages;

        $wizard = StringUtil::deserialize($this->wizard);

        foreach ($languages as $languageKey => $languageLabel) {
            $this->arrOptions[0]['label'] = sprintf($this->blankOptionLabel, $languageLabel);
            $this->strLabel = $languageLabel;

            $fields[] = sprintf(
                '<select name="%s[]" id="ctrl_%s" class="%s%s"%s onfocus="Backend.getScrollOffset()">%s</select>%s',
                $this->strName,
                sprintf('%s-%s', $this->strId, $languageLabel),
                $cssClasses,
                ($this->strClass ? ' '.$this->strClass : ''),
                $this->getAttributes(),
                implode('', $this->getOptions($languageKey)),
                $wizard[$languageKey] ?? ''
            );
        }

        $this->strLabel = $label;

        return implode('', $fields);
    }

    protected function isSelected($option): string
    {
        if (empty($this->varValue) && empty($_POST) && ($option['default'] ?? null)) {
            return static::optionSelected((string) 1, 1);
        }

        return static::optionSelected(
            $option['value'] ?? null,
            $this->varValue[$option['index']] ?? null
        );
    }

    private function getOptions(string $index): array
    {
        $options = [];

        foreach ($this->arrOptions as $key => $option) {
            $option['index'] = $index;

            if (isset($option['value'])) {
                $options[] = sprintf(
                    '<option value="%s"%s>%s</option>',
                    StringUtil::specialchars($option['value']),
                    $this->isSelected($option),
                    $option['label']
                );
            } else {
                $optionGroups = [];

                foreach ($option as $optionGroup) {
                    $optionGroups[] = sprintf(
                        '<option value="%s"%s>%s</option>',
                        StringUtil::specialchars($optionGroup['value']),
                        $this->isSelected($optionGroup),
                        $optionGroup['label']
                    );
                }

                $options[] = sprintf(
                    '<optgroup label="&nbsp;%s">%s</optgroup>',
                    StringUtil::specialchars($key),
                    implode('', $optionGroups)
                );
            }
        }

        return $options;
    }
}
