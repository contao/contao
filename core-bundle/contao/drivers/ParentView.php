<?php

namespace Contao;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\String\HtmlAttributes;

class ParentView extends View
{
	protected string $mode = 'parent';
	protected object $objParent;
	protected bool $hasSorting;

	public function __construct(&$dc, $table, $intMode, $objParent, $blnHasSorting)
	{
		$this->objParent = $objParent;
		$this->hasSorting = $blnHasSorting;
		parent::__construct($dc, $table, $intMode);
	}

	public function formatHeaderFields(){
		$security = System::getContainer()->get('security.helper');
		$headerFields = $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['headerFields'];
		foreach ($headerFields as $v)
		{
			$_v = StringUtil::deserialize($this->objParent->$v);

			// Translate UUIDs to paths
			if (($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['inputType'] ?? null) == 'fileTree')
			{
				$objFiles = FilesModel::findMultipleByUuids((array) $_v);

				if ($objFiles !== null)
				{
					$_v = $objFiles->fetchEach('path');
				}
			}

			if (\is_array($_v))
			{
				$_v = implode(', ', $_v);
			}
			elseif (($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['eval']['isBoolean'] ?? null) || (($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['inputType'] ?? null) == 'checkbox' && !($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['eval']['multiple'] ?? null)))
			{
				$_v = $_v ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
			}
			elseif (($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['eval']['rgxp'] ?? null) == 'date')
			{
				$_v = $_v ? Date::parse(Config::get('dateFormat'), $_v) : '-';
			}
			elseif (($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['eval']['rgxp'] ?? null) == 'time')
			{
				$_v = $_v ? Date::parse(Config::get('timeFormat'), $_v) : '-';
			}
			elseif (($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['eval']['rgxp'] ?? null) == 'datim')
			{
				$_v = $_v ? Date::parse(Config::get('datimFormat'), $_v) : '-';
			}
			elseif ($v == 'tstamp')
			{
				$_v = Date::parse(Config::get('datimFormat'), $this->objParent->tstamp);
			}
			elseif (isset($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['foreignKey']))
			{
				$arrForeignKey = explode('.', $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['foreignKey'], 2);

				$objLabel = Database::getInstance()
					->prepare("SELECT " . Database::quoteIdentifier($arrForeignKey[1]) . " AS value FROM " . $arrForeignKey[0] . " WHERE id=?")
					->limit(1)
					->execute($_v);

				$_v = $objLabel->numRows ? $objLabel->value : '-';
			}
			elseif (\is_array($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['reference'][$_v] ?? null))
			{
				$_v = $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['reference'][$_v][0];
			}
			elseif (isset($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['reference'][$_v]))
			{
				$_v = $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['reference'][$_v];
			}
			elseif (($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['options'] ?? null))
			{
				$_v = $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['options'][$_v] ?? null;
			}
			elseif (\is_array($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['options_callback'] ?? null))
			{
				$strClass = $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['options_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['options_callback'][1];

				$options_callback = System::importStatic($strClass)->$strMethod($this);

				$_v = $options_callback[$_v] ?? '-';
			}
			elseif (\is_callable($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['options_callback'] ?? null))
			{
				$options_callback = $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['options_callback']($this);

				$_v = $options_callback[$_v] ?? '-';
			}

			// Add the sorting field
			if ($_v)
			{
				if (isset($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['label']))
				{
					$key = \is_array($GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['label']) ? $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['label'][0] : $GLOBALS['TL_DCA'][$this->dc->ptable]['fields'][$v]['label'];
				}
				else
				{
					$key = $GLOBALS['TL_LANG'][$this->dc->ptable][$v][0] ?? $v;
				}

				$add[$key] = $_v;
			}
		}

		// Trigger the header_callback (see #3417)
		if (\is_array($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['header_callback'] ?? null))
		{
			$add = System::importStatic($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['header_callback'][0])->{$GLOBALS['TL_DCA'][$this->table]['list']['sorting']['header_callback'][1]}($add, $this->dc);
		}
		elseif (\is_callable($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['header_callback'] ?? null))
		{
			$add = $GLOBALS['TL_DCA'][$this->table]['list']['sorting']['header_callback']($add, $this->dc);
		}

		return $add;
	}

	public function getActionsLabels()
	{
		$actionsLabels['cut'] = $GLOBALS['TL_LANG'][$this->table]['cut'] ?? $GLOBALS['TL_LANG']['DCA']['cut'];
		$actionsLabels['pasteNew'] = $GLOBALS['TL_LANG'][$this->table]['pastenew'] ?? $GLOBALS['TL_LANG']['DCA']['pastenew'];
		$actionsLabels['pasteAfter'] = $GLOBALS['TL_LANG'][$this->table]['pasteafter'] ?? $GLOBALS['TL_LANG']['DCA']['pasteafter'];

		return $actionsLabels;
	}

	public function getOperations()
	{
		$actionsLabels = $this->getActionsLabels();
		$operations = $this->dc->generateHeaderButtons($this->objParent->row(), $this->dc->ptable);

		if ($this->hasSorting && !($GLOBALS['TL_DCA'][$this->table]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->table]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->table, new CreateAction($this->table, $this->addDynamicPtable(['pid' => $this->objParent->id, 'sorting' => 0])))) {
			$operations->append([
				'href' => $this->addToUrl('act=create&amp;mode=2&amp;pid=' . $this->objParent->id . '&amp;id=' . $this->intId),
				'title' => $actionsLabels['pasteNew'][0],
				'icon' => Image::getHtml('new.svg', $actionsLabels['pasteNew'][0]),
			]);
		}

		return $operations;
	}

	public function renderHeader($children, $pagination)
	{

		$reset = $this->dc->strPickerFieldType == 'radio' ? $this->twig->render('@Contao/backend/listing/be_select_reset.html.twig', ['label' => $GLOBALS['TL_LANG']['MSC']['resetSelected'], 'context' => $this->getContext()]) : '';

		$objAttributes = new HtmlAttributes([]);

		$arrObjects = [
			'Image' => new Image(),
			'Parent' => $this->objParent,
		];

		return $this->twig->render('@Contao/backend/listing/be_parent_view.html.twig', [
			'attributes' => $objAttributes,
			'context' => $this->getContext(),
			'children' => $children,
			'reset' => $reset,
			'pagination' => str_contains($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['panelLayout'] ?? '', 'limit') ? $pagination : '',
			'listingClasses' => ($GLOBALS['TL_DCA'][$this->table]['list']['sorting']['renderAsGrid'] ?? false) ? ' as-grid' : '',
			'actions' => $this->twig->render('@Contao/backend/listing/be_parent_view_actions.html.twig', [
				'operations' => $this->getOperations(),
				'actionsLabels' => $this->getActionsLabels(),
				'isSelecting' => Input::get('act') == 'select' || $this->dc->strPickerFieldType == 'checkbox',
				...$this->getClipboardStuff(),
				...$arrObjects
			])
		]);
	}

	public function render($children, $pagination)
	{
		$return = $this->renderHeader($children, $pagination);

		// Form
		if (Input::get('act') == 'select') {
			$strButtons = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($this->table, $this->hasSorting, $this->dc);

			$return = $this->renderSelectForm($return, $strButtons);
		}

		return $return;
	}
}
