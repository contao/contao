<?php

namespace Contao;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\String\HtmlAttributes;

class ListView extends View
{
	protected string $mode = 'list';
	protected array $items;
	protected string $initialOrderBy;

	public function __construct(&$dc, $table, $intMode, $items, $initialOrderBy)
	{
		$this->items = $items;
		$this->initialOrderBy = $initialOrderBy;
		parent::__construct($dc, $table, $intMode);
	}

	public function renderWithTableHeader($arrVars)
	{
		$arrFields = [];
		foreach ($GLOBALS['TL_DCA'][$this->table]['list']['label']['fields'] as $strField) {
			if (str_contains($strField, ':')) {
				[$strField] = explode(':', $strField, 2);
			}
			$arrFields[] = $strField;
		}

		return $this->twig->render('@Contao/backend/listing/be_list_view_header.html.twig', [
			'context' => $this->getContext(),
			'headerFields' => $arrFields,
			'fields' => $GLOBALS['TL_DCA'][$this->table]['fields'],
			'initialOrderBy' => $this->initialOrderBy,
			...$arrVars
		]);
	}

	public function renderItems()
	{
		$showColumns = ($GLOBALS['TL_DCA'][$this->table]['list']['label']['showColumns'] ?? null);
		$arrReturn = [];
		$remoteCur = false;
		$limitHeight = BackendUser::getInstance()->doNotCollapse ? false : (int)($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['limitHeight'] ?? 0);

		$i = 0;
		foreach ($this->items as $row) {
			// Improve performance for $dc->getCurrentRecord($id);
			$this->dc::setCurrentRecordCache($row['id'], $this->table, $row);

			$this->dc->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->table, new ReadAction($this->table, $row));

			$this->dc->current[] = $row['id'];
			$label = $this->dc->generateRecordLabel($row, $this->table);

			// Build the sorting groups
			if ($this->intMode > 0) {
				$current = $row[$this->initialOrderBy];
				$orderBy = $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['fields'] ?? ['id'];
				$sortingMode = (\count($orderBy) == 1 && $this->initialOrderBy == $orderBy[0] && ($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['flag'] ?? null) && !($GLOBALS['TL_DCA'][$this->table]['fields'][$this->initialOrderBy]['flag'] ?? null)) ? $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['flag'] : ($GLOBALS['TL_DCA'][$this->table]['fields'][$this->initialOrderBy]['flag'] ?? null);
				$remoteNew = $this->dc->formatCurrentValue($this->initialOrderBy, $current, $sortingMode);

				// Add the group header
				if (($remoteNew != $remoteCur || $remoteCur === false) && !($GLOBALS['TL_DCA'][$this->table]['list']['label']['showColumns'] ?? null) && !($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['disableGrouping'] ?? null)) {
					$group = $this->dc->formatGroupHeader($this->initialOrderBy, $remoteNew, $sortingMode, $row);
					$remoteCur = $remoteNew;

					$header = $group;
				}
			}

			$colspan = 1;

			// Handle strings and arrays
			if (!$showColumns) {
				$label = \is_array($label) ? implode(' ', $label) : $label;
			} elseif (!\is_array($label)) {
				$label = [$label];
				$colspan = \count($GLOBALS['TL_DCA'][$this->table]['list']['label']['fields'] ?? []);
			}

			// Show columns
			if ($showColumns) {
				$arrFieldValues = [];
				foreach ($label as $j => $arg) {
					$field = $GLOBALS['TL_DCA'][$this->table]['list']['label']['fields'][$j] ?? null;
					$value = (string)$arg !== '' ? $arg : '-';

					$arrFieldValues[] = [
						'name' => $field,
						'class' => explode(':', $field, 2)[0],
						'value' => $value
					];
				}
			}

			$objAttributes = new HtmlAttributes([
				'class' => ((string)($row['tstamp'] ?? null) === '0' ? 'draft ' : ''),
			]);

			$arrReturn[] = $this->twig->render('@Contao/backend/listing/be_list_view_item.html.twig', [
				'attributes' => $objAttributes,
				'item' => $row,
				// Buttons ($row, $table, $root, $blnCircularReference, $children, $previous, $next)
				'buttons' => $this->dc->generateButtons($row, $this->table, $this->dc->root) . ($this->dc->strPickerFieldType ? $this->getPickerInputField($row['id']) : ''),
				'header' => $header ?? false,
				'loop' => $i,
				'showColumns' => $showColumns,
				'fields' => $arrFieldValues,
				'initialOrderBy' => $this->initialOrderBy,
				'limitHeight' => $limitHeight,
				'label' => $label,
				'colspan' => $colspan,
				'isSelecting' => Input::get('act') == 'select'
			]);

			$i++;
		}

		return implode('', $arrReturn);
	}

	public function render($pagination)
	{
		$reset = $this->dc->strPickerFieldType == 'radio' ? $this->twig->render('@Contao/backend/listing/be_select_reset.html.twig', ['label' => $GLOBALS['TL_LANG']['MSC']['resetSelected'], 'context' => $this->getContext()]) : '';

		$children = $this->renderItems();

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
			'pagination' => str_contains($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['panelLayout'] ?? '', 'limit') ? $pagination : '',
			...$this->getClipboardStuff()
		];


		$showColumns = ($GLOBALS['TL_DCA'][$this->table]['list']['label']['showColumns'] ?? null);
		$return = $showColumns ? $this->renderWithTableHeader($arrVars, $this->initialOrderBy) : $this->twig->render('@Contao/backend/listing/be_list_view.html.twig', $arrVars);

		if (Input::get('act') == 'select') {
			$strButtons = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($this->table, false, $this);

			//MISSING OPENER
			$return .= '</div>';
			$return = $this->renderSelectForm($return, $strButtons);
		}

		return $return;
	}
}
