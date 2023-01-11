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
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentSelect extends SelectMenu
{
	public function generate(): string
	{
		/** @var ContaoFramework $framework */
		$framework = System::getContainer()->get('contao.framework');

		/** @var TranslatorInterface $translator */
		$translator = System::getContainer()->get('translator');

		$fields = array();
		$originalLabel = $this->strLabel;
		$cssClasses = 'tl_select tl_chosen';
		$rootPages = $framework->getAdapter(PageModel::class)->findByType('root', array('order' => 'sorting'));
		$wizard = StringUtil::deserialize($this->wizard);

		$this->blankOptionLabel = $translator->trans(sprintf('tl_module.%sBlankOptionLabel', $this->name), array(), 'contao_tl_module');

		foreach ($rootPages as $rootPage)
		{
			$this->arrOptions[0]['label'] = sprintf($this->blankOptionLabel, $rootPage->title);
			$this->strLabel = $rootPage->title;

			$fields[] = sprintf(
				'<select name="%s[]" id="ctrl_%s" class="%s%s"%s onfocus="Backend.getScrollOffset()">%s</select>%s',
				$this->strName,
				sprintf('%s-%s', $this->strId, $rootPage->id),
				$cssClasses,
				($this->strClass ? ' ' . $this->strClass : ''),
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

		foreach ($this->arrOptions as $option)
		{
			$option['index'] = $rootPage->id;

			if (isset($option['value']))
			{
				if ($this->isSelected($option))
				{
					$option['label'] = sprintf(
						'%s <span class="label-info">[%s]</span>',
						$option['label'],
						$rootPage->title,
					);
				}

				$options[] = sprintf(
					'<option value="%s"%s>%s</option>',
					self::specialcharsValue($option['value']),
					$this->isSelected($option),
					$option['label']
				);
			}
		}

		return $options;
	}
}
