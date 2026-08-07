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
 * Provide methods to handle list items.
 *
 * @property integer $maxlength
 */
class ListWizard extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	protected bool $legacyContentElement = false;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);

		/** @deprecated Deprecated since Contao 6.1, to be removed in Contao 7 */
		$this->legacyContentElement = ($GLOBALS['TL_CTE']['texts']['list'] ?? null) === ContentList::class;
	}

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		if ($strKey == 'maxlength')
		{
			if ($varValue > 0)
			{
				$this->arrAttributes['maxlength'] = $varValue;
			}
		}
		else
		{
			parent::__set($strKey, $varValue);
		}
	}

	public function validate(): void
	{
		parent::validate();

		if ($this->legacyContentElement)
		{
			return;
		}

		$this->varValue = $this->normalize($this->varValue);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		// Make sure there is at least an empty array
		if (empty($this->varValue) || !\is_array($this->varValue))
		{
			$this->varValue = array('');
		}

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/list_wizard.html.twig', array(
			'id' => $this->strId,
			'rows' => $this->legacyContentElement ? $this->varValue : $this->normalize($this->varValue),
			'is_legacy' => $this->legacyContentElement,
		));
	}

	private function normalize($varValue): array
	{
		$arrRows = array();

		foreach ((array) $varValue as $varRow)
		{
			if (!\is_array($varRow))
			{
				$varRow = array('item' => $varRow);
			}

			$arrRows[] = array(
				'item' => (string) ($varRow['item'] ?? ''),
				'list' => $this->normalize($varRow['list'] ?? array()),
			);
		}

		return $arrRows;
	}
}
