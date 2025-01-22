<?php

namespace Contao;

use Contao\CoreBundle\String\HtmlAttributes;

class ListView extends View
{
	protected string $mode = 'list';

	public function render($children)
	{
		$twig = System::getContainer()->get('twig');

		$reset = $this->dc->strPickerFieldType == 'radio' ? $twig->render('@Contao/backend/listing/be_select_reset.html.twig', ['label' => $GLOBALS['TL_LANG']['MSC']['resetSelected'], 'context' => $this->getContext()]) : '';

		$objAttributes = new HtmlAttributes([
			$this->dc->getPickerValueAttribute()
		]);

		$objAttributesInner = new HtmlAttributes([
			'class' => "tl_listing" .(($GLOBALS['TL_DCA'][$this->table]['list']['label']['showColumns'] ?? null) ? ' showColumns' : '') . ($this->dc->strPickerFieldType ? ' picker unselectable' : '')
		]);

		$return = $twig->render('@Contao/backend/listing/be_list_view.html.twig', [
			'attributes' => $objAttributes,
			'wrapper_attributes' => $objAttributesInner,
			'context' => $this->getContext(),
			'children' => $children,
			'reset' => $reset,
			...$this->getClipboardStuff()
		]);

		return $return;
	}
}
