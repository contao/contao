<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\ContentElement\TableController;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class is deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentTable::class, TableController::class);

/**
 * Front end content element "table".
 *
 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
 *             use Contao\CoreBundle\Controller\ContentElement\TableController instead.
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
		$this->Template->useHeader = $this->thead;
		$this->Template->useFooter = $this->tfoot;
		$this->Template->useLeftTh = $this->tleft;
		$this->Template->sortable = $this->sortable;

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
			foreach ($rows[0] as $v)
			{
				$arrHeader[] = (string) $v !== '' ? $this->nl2br($v) : '&nbsp;';
			}

			array_shift($rows);
		}

		$this->Template->header = $arrHeader;
		$limit = $this->tfoot ? (\count($rows)-1) : \count($rows);

		// Table body
		for ($j=0; $j<$limit; $j++)
		{
			foreach ($rows[$j] as $v)
			{
				$arrBody[$j][] = (string) $v !== '' ? $this->nl2br($v) : '&nbsp;';
			}
		}

		$this->Template->body = $arrBody;

		// Table footer
		if ($this->tfoot)
		{
			foreach ($rows[\count($rows) - 1] as $v)
			{
				$arrFooter[] = (string) $v !== '' ? $this->nl2br($v) : '&nbsp;';
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
