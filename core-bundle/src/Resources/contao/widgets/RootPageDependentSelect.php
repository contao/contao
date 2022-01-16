<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

class RootPageDependentSelect extends SelectMenu
{
	public function generate(): string
	{
		$fields = array();
		$originalLabel = $this->strLabel;
		$cssClasses = 'tl_select tl_chosen';
		$rootPages = PageModel::findByType('root', array('order' => 'sorting'));
		$wizard = StringUtil::deserialize($this->wizard);

		$this->blankOptionLabel = $GLOBALS['TL_LANG']['tl_module'][sprintf('%sBlankOptionLabel', $this->name)];

		foreach ($rootPages as $rootPage)
		{
			$label = sprintf('%s (%s)', $rootPage->title, $rootPage->language);
			$this->arrOptions[0]['label'] = sprintf($this->blankOptionLabel, $label);
			$this->strLabel = $label;

			$fields[] = sprintf(
				'<select name="%s[]" id="ctrl_%s" class="%s%s"%s onfocus="Backend.getScrollOffset()">%s</select>%s',
				$this->strName,
				sprintf('%s-%s', $this->strId, $label),
				$cssClasses,
				($this->strClass ? ' ' . $this->strClass : ''),
				$this->getAttributes(),
				implode('', $this->getOptions($rootPage->id)),
				$wizard[$rootPage->id] ?? ''
			);
		}

		$this->strLabel = $originalLabel;

		return implode('', $fields);
	}

	protected function isSelected($arrOption): string
	{
		if (empty($this->varValue) && empty($_POST) && ($arrOption['default'] ?? null))
		{
			return ' selected';
		}

		return static::optionSelected(
			$arrOption['value'] ?? null,
			$this->varValue[$arrOption['index']] ?? null
		);
	}

	private function getOptions(string $index): array
	{
		$options = array();

		foreach ($this->arrOptions as $key => $option)
		{
			$option['index'] = $index;

			if (isset($option['value']))
			{
				$options[] = sprintf(
					'<option value="%s"%s>%s</option>',
					StringUtil::specialchars($option['value']),
					$this->isSelected($option),
					$option['label']
				);
			}
			else
			{
				$optionGroups = array();

				foreach ($option as $optionGroup)
				{
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
