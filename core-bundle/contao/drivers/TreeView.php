<?php

namespace Contao;

use Contao\CoreBundle\String\HtmlAttributes;

class TreeView extends View
{
	protected string $mode = 'tree';

	public function render($children, $strClass ,$blnHasSorting)
	{
		$twig = System::getContainer()->get('twig');
		$arrClipboard = System::getContainer()->get('contao.data_container.clipboard_manager')->get($this->table);
		$blnClipboard = null !== $arrClipboard;

		$return = "";

		$breadcrumb = $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['breadcrumb'] ?? '';

		$_buttons = '&nbsp;';

		// Show paste button only if there are no root records specified
		if ($blnClipboard && $this->intMode == DataContainer::MODE_TREE && $this->dc->rootPaste && Input::get('act') != 'select') {
			$operations = System::getContainer()->get('contao.data_container.operations_builder')->initialize();

			// Call paste_button_callback (&$dc, $row, $this->table, $cr, $children, $previous, $next)
			if (\is_array($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['paste_button_callback'] ?? null)) {
				$strClass = $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['paste_button_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['paste_button_callback'][1];

				$operations->append(['primary' => true, 'html' => System::importStatic($strClass)->$strMethod($this->dc, ['id' => 0], $this->table, false, $arrClipboard)]);
			} elseif (\is_callable($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['paste_button_callback'] ?? null)) {
				$operations->append(['primary' => true, 'html' => $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['paste_button_callback']($this->dc, ['id' => 0], $this->table, false, $arrClipboard)]);
			} elseif (!$this->dc->canPasteClipboard($arrClipboard, ['pid' => 0, 'sorting' => 0])) {
				$operations->append(['primary' => true, 'icon' => Image::getHtml('pasteinto--disabled.svg')]);
			} else {
				$labelPasteInto = $GLOBALS['TL_LANG'][$this->table]['pasteinto'] ?? $GLOBALS['TL_LANG']['DCA']['pasteinto'];
				$imagePasteInto = Image::getHtml('pasteinto.svg', $labelPasteInto[0]);

				$operations->append([
					'href' => $this->dc->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=0' . (!\is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')),
					'title' => $labelPasteInto[0],
					'attributes' => 'data-action="contao--scroll-offset#store"',
					'icon' => $imagePasteInto,
					'primary' => true,
				]);
			}

			$_buttons .= $operations;
		}

		$reset = $this->dc->strPickerFieldType == 'radio' ? $twig->render('@Contao/backend/listing/be_select_reset.html.twig', ['label' => $GLOBALS['TL_LANG']['MSC']['resetSelected'], 'context' => $this->getContext()]) : '';

		$objAttributes = new HtmlAttributes([
			$this->dc->getPickerValueAttribute()
		]);

		$objAttributesInner = new HtmlAttributes([
			'class' => "tl_listing $strClass" . ($this->dc->strPickerFieldType ? ' picker unselectable' : '')
		]);

		$return .= $twig->render('@Contao/backend/listing/be_tree_view.html.twig', [
			'attributes' => $objAttributes,
			'wrapper_attributes' => $objAttributesInner,
			'breadcrumbs' => $breadcrumb,
			'operations' => $_buttons,
			'children' => $children,
			'reset' => $reset,
			'context' => $this->getContext(),
			...$this->getClipboardStuff()
		]);

		// Form
		if (Input::get('act') == 'select') {
			$strButtons = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($this->table, $blnHasSorting, $this->dc);

			$return = $this->renderSelectForm($return, $strButtons);
		}

		return $return;
	}
}
