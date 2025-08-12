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
 * Provide methods to handle textareas.
 *
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property boolean $rte
 * @property integer $rows
 * @property integer $cols
 */
class TextArea extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Add a for attribute
	 * @var boolean
	 */
	protected $blnForAttribute = true;

	/**
	 * Rows
	 * @var integer
	 */
	protected $intRows = 12;

	/**
	 * Columns
	 * @var integer
	 */
	protected $intCols = 80;

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
			case 'maxlength':
				if ($varValue > 0)
				{
					$this->arrAttributes['maxlength'] = $varValue;
				}
				break;

			case 'mandatory':
				if ($varValue)
				{
					$this->arrAttributes['required'] = 'required';
				}
				else
				{
					unset($this->arrAttributes['required']);
				}
				parent::__set($strKey, $varValue);
				break;

			case 'placeholder':
				$this->arrAttributes['placeholder'] = $varValue;
				break;

			case 'rows':
				$this->intRows = $varValue;
				break;

			case 'cols':
				$this->intCols = $varValue;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Unify newline characters to UNIX style.
	 * This will also prevent our maxlength from counting newlines as two characters.
	 */
	protected function getPost($strKey)
	{
		return str_replace("\r\n", "\n", parent::getPost($strKey));
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$disableAutogrow = $this->rte || str_contains($this->strClass, 'noresize') || (isset($this->arrConfiguration['autogrow']) && $this->arrConfiguration['autogrow']);

		return \sprintf(
			'<textarea name="%s" id="ctrl_%s" class="tl_textarea%s" rows="%s" cols="%s"%s%s data-action="focus->contao--scroll-offset#store" data-contao--scroll-offset-target="autoFocus">%s</textarea>%s',
			$this->strName,
			$this->strId,
			$this->strClass ? ' ' . $this->strClass : '',
			$disableAutogrow ? $this->intRows : 1, // Let the controller handle the height
			$this->intCols,
			$disableAutogrow ? '' : ' data-controller="contao--textarea-autogrow"',
			$this->getAttributes(),
			self::specialcharsValue($this->varValue),
			$this->wizard
		);
	}
}
