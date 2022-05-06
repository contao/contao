<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Front end content element "table".
 */
class ContentTable extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_table';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$rows = StringUtil::deserialize($this->tableitems, true);

		$this->Template->id = 'table_' . $this->id;
		$this->Template->summary = StringUtil::specialchars($this->summary);
		$this->Template->useHeader = $this->thead ? true : false;
		$this->Template->useFooter = $this->tfoot ? true : false;
		$this->Template->useLeftTh = $this->tleft ? true : false;
		$this->Template->sortable = $this->sortable ? true : false;

		if ($this->sortable)
		{
			$this->Template->sortDefault = $this->sortIndex . '|' . ($this->sortOrder == 'descending' ? 'desc' : 'asc');
		}

		$arrHeader = array();
		$arrBody = array();
		$arrFooter = array();

		// Table header
		if ($this->thead)
		{
			foreach ($rows[0] as $i=>$v)
			{
				$arrHeader[] = array
				(
					'class' => 'head_' . $i . (($i == 0) ? ' col_first' : '') . (($i == (\count($rows[0]) - 1)) ? ' col_last' : '') . (($i == 0 && $this->tleft) ? ' unsortable' : ''),
					'content' => ((string) $v !== '' ? $this->nl2br($v) : '&nbsp;')
				);
			}

			array_shift($rows);
		}

		$this->Template->header = $arrHeader;
		$limit = $this->tfoot ? (\count($rows)-1) : \count($rows);

		// Table body
		for ($j=0; $j<$limit; $j++)
		{
			$class_tr = '';

			if ($j == 0)
			{
				$class_tr .= ' row_first';
			}

			if ($j == ($limit - 1))
			{
				$class_tr .= ' row_last';
			}

			$class_eo = (($j % 2) == 0) ? ' odd' : ' even';

			foreach ($rows[$j] as $i=>$v)
			{
				$class_td = '';

				if ($i == 0)
				{
					$class_td .= ' col_first';
				}

				if ($i == (\count($rows[$j]) - 1))
				{
					$class_td .= ' col_last';
				}

				$arrBody['row_' . $j . $class_tr . $class_eo][] = array
				(
					'class' => 'col_' . $i . $class_td,
					'content' => ((string) $v !== '' ? $this->nl2br($v) : '&nbsp;')
				);
			}
		}

		$this->Template->body = $arrBody;

		// Table footer
		if ($this->tfoot)
		{
			foreach ($rows[(\count($rows)-1)] as $i=>$v)
			{
				$arrFooter[] = array
				(
					'class' => 'foot_' . $i . (($i == 0) ? ' col_first' : '') . (($i == (\count($rows[(\count($rows)-1)]) - 1)) ? ' col_last' : ''),
					'content' => ((string) $v !== '' ? $this->nl2br($v) : '&nbsp;')
				);
			}
		}

		$this->Template->footer = $arrFooter;
	}

	/**
	 * Convert new lines to <br> tags if there are no HTML block elements
	 *
	 * @param string $strString
	 *
	 * @return string
	 */
	private function nl2br($strString)
	{
		if (preg_match('#<(?:address|blockquote|dd|div|dl|dt|figcaption|figure|h[1-6]|hr|li|ol|p|pre|ul)[ >]#', $strString))
		{
			return $strString;
		}

		return nl2br($strString, false);
	}
}

class_alias(ContentTable::class, 'ContentTable');
