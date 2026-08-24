<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Provide methods to handle table fields.
 *
 * @property integer $rows
 * @property integer $cols
 */
class TableWizard extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Rows
	 * @var integer
	 */
	protected int $intRows = 12;

	/**
	 * Columns
	 * @var integer
	 */
	protected int $intCols = 80;

	/**
	 * Label that is passed to the widget template
	 * @var string
	 */
	protected string $widgetLabel = '';

	/**
	 * @var array<string, string>
	 */
	protected array $arrAppearance = array('head' => 'thead', 'foot' => 'tfoot', 'left' => 'tleft');

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'rows':
				$this->intRows = $varValue;
				break;

			case 'cols':
				$this->intCols = $varValue;
				break;

			case 'appearance':
				$this->arrAppearance = \is_array($varValue) ? $varValue : array();
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$rows = $this->normalizeValue();
		$colCount = \count($rows[0]);

		$tableRows = array();

		foreach ($rows as $i => $cells)
		{
			$tableCells = array();

			foreach ($cells as $j => $value)
			{
				$tableCells[] = array(
					'name' => $this->strId . '[' . $i . '][' . $j . ']',
					'value' => $value,
				);
			}

			$tableRows[] = array('cells' => $tableCells);
		}

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/table_wizard.html.twig', array(
			'id' => $this->strId,
			'class' => $this->strClass,
			'rows' => $tableRows,
			'row_count' => \count($tableRows),
			'col_count' => $colCount,
			'textarea_rows' => $this->intRows,
			'textarea_cols' => $this->intCols,
			'cell_attributes' => $this->arrAttributes,
			'appearance' => $this->arrAppearance,
			'import_url' => Backend::addToUrl('key=table'),
		));
	}

	/**
	 * Make sure there is at least an empty array
	 *
	 * @return array<int, array<int, string>>
	 */
	private function normalizeValue(): array
	{
		if (empty($this->varValue) || !\is_array($this->varValue))
		{
			$this->varValue = array(array(''));
		}

		$colCount = 1;

		foreach ($this->varValue as $row)
		{
			if (\is_array($row))
			{
				$colCount = max($colCount, \count($row));
			}
		}

		$rows = array();

		foreach ($this->varValue as $row)
		{
			$row = \is_array($row) ? array_values($row) : array((string) $row);
			$cells = array();

			for ($j = 0; $j < $colCount; ++$j)
			{
				$cells[] = (string) ($row[$j] ?? '');
			}

			$rows[] = $cells;
		}

		return $rows;
	}
}
