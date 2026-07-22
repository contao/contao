<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Framework\ContaoFramework;

class RootPageDependentSelect extends SelectMenu
{
	public function generate(): string
	{
		/** @var ContaoFramework $framework */
		$framework = System::getContainer()->get('contao.framework');
		$translator = System::getContainer()->get('translator');

		$fields = array();
		$originalLabel = $this->strLabel;
		$rootPages = $framework->getAdapter(PageModel::class)->findByType('root', array('order' => 'sorting'));
		$wizard = StringUtil::deserialize($this->wizard);
		$name = $this->arrAttributes['strField'] ?? 'rootPageDependentModules';

		$this->blankOptionLabel = $translator->trans(\sprintf('tl_module.%sBlankOptionLabel', $name), array(), 'contao_tl_module');

		foreach ($rootPages as $rootPage)
		{
			$this->arrOptions[0]['label'] = \sprintf($this->blankOptionLabel, $rootPage->title, $rootPage->dns);
			$this->strLabel = $rootPage->title;

			$fields[] = \sprintf(
				'<div class="tl_select_wrapper" data-controller="contao--choices"><select name="%s[%s]" id="ctrl_%s" class="tl_select%s"%s data-action="focus->contao--scroll-offset#store">%s</select></div>%s',
				$this->strName,
				$rootPage->id,
				\sprintf('%s-%s', $this->strId, $rootPage->id),
				$this->strClass ? ' ' . $this->strClass : '',
				$this->getAttributes(),
				implode('', $this->getOptions($rootPage)),
				$wizard[$rootPage->id] ?? ''
			);
		}

		$this->strLabel = $originalLabel;

		return implode('', $fields);
	}

	protected function isSelected($arrOption): string
	{
		if (empty($this->varValue) && !Input::isPost() && ($arrOption['default'] ?? null))
		{
			return ' selected';
		}

		return static::optionSelected($arrOption['value'] ?? null, $this->varValue[$arrOption['index']] ?? null);
	}

	private function getOptions(PageModel $rootPage): array
	{
		$options = array();

		foreach ($this->arrOptions as $key => $option)
		{
			if (isset($option['value']))
			{
				$option['index'] = $rootPage->id;

				if ($this->isSelected($option))
				{
					$option['label'] = \sprintf(
						'%s <span class="label-info">[%s]</span>',
						$option['label'],
						$rootPage->title,
					);
				}

				$options[] = \sprintf(
					'<option value="%s"%s>%s</option>',
					self::specialcharsValue($option['value']),
					$this->isSelected($option),
					$option['label']
				);
			}
			else
			{
				$optgroups = array();

				foreach ($option as $optgroup)
				{
					$optgroup['index'] = $rootPage->id;

					if ($this->isSelected($optgroup))
					{
						$optgroup['label'] = \sprintf(
							'%s <span class="label-info">[%s]</span>',
							$optgroup['label'],
							$rootPage->title,
						);
					}

					$optgroups[] = \sprintf(
						'<option value="%s"%s>%s</option>',
						self::specialcharsValue($optgroup['value']),
						$this->isSelected($optgroup),
						$optgroup['label']
					);
				}

				$options[] = \sprintf('<optgroup label="%s">%s</optgroup>', StringUtil::specialchars($key), implode('', $optgroups));
			}
		}

		return $options;
	}
}
