<?php

namespace Contao;

use Contao\CoreBundle\String\HtmlAttributes;

class ListView extends View
{
	protected string $mode = 'list';

	public function renderWithTableHeader($arrVars, $initialOrderBy)
	{
		$twig = System::getContainer()->get('twig');

		$arrFields = [];
		foreach ($GLOBALS['TL_DCA'][$this->table]['list']['label']['fields'] as $strField) {
			if (str_contains($strField, ':')) {
				[$strField] = explode(':', $strField, 2);
			}
			$arrFields[] = $strField;
		}

		return $twig->render('@Contao/backend/listing/be_list_view_header.html.twig', [
			'context' => $this->getContext(),
			'headerFields' => $arrFields,
			'fields' => $GLOBALS['TL_DCA'][$this->table]['fields'],
			'initialOrderBy' => $initialOrderBy,
			...$arrVars
		]);
	}

	public function render($children, $initialOrderBy)
	{
		$twig = System::getContainer()->get('twig');

		$reset = $this->dc->strPickerFieldType == 'radio' ? $twig->render('@Contao/backend/listing/be_select_reset.html.twig', ['label' => $GLOBALS['TL_LANG']['MSC']['resetSelected'], 'context' => $this->getContext()]) : '';

		$objAttributes = new HtmlAttributes([
			$this->dc->getPickerValueAttribute()
		]);

		$objAttributesInner = new HtmlAttributes([
			'class' => "tl_listing" . (($GLOBALS['TL_DCA'][$this->table]['list']['label']['showColumns'] ?? null) ? ' showColumns' : '') . ($this->dc->strPickerFieldType ? ' picker unselectable' : '')
		]);

		$arrVars = [
			'attributes' => $objAttributes,
			'wrapper_attributes' => $objAttributesInner,
			'context' => $this->getContext(),
			'children' => $children,
			'reset' => $reset,
			...$this->getClipboardStuff()
		];


		$showColumns = ($GLOBALS['TL_DCA'][$this->table]['list']['label']['showColumns'] ?? null);
		$return = $showColumns ? $this->renderWithTableHeader($arrVars, $initialOrderBy) : $twig->render('@Contao/backend/listing/be_list_view.html.twig', $arrVars);

		return $return;
	}
}
