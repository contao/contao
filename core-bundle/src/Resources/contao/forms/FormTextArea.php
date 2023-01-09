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
 * Class FormTextArea
 *
 * @property string  $value
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property string  $placeholder
 * @property string  $size
 * @property integer $rows
 * @property integer $cols
 *
 * @todo Rename to FormTextarea in Contao 5.0
 */
class FormTextArea extends Widget
{
	/**
	 * Submit user input
	 *
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Add a for attribute
	 *
	 * @var boolean
	 */
	protected $blnForAttribute = true;

	/**
	 * Rows
	 *
	 * @var integer
	 */
	protected $intRows = 12;

	/**
	 * Columns
	 *
	 * @var integer
	 */
	protected $intCols = 80;

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_textarea';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-textarea';

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey   The attribute name
	 * @param mixed  $varValue The attribute value
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

			case 'size':
				$arrSize = StringUtil::deserialize($varValue);

				if (isset($arrSize[0]))
				{
					$this->intRows = $arrSize[0];
				}

				if (isset($arrSize[1]))
				{
					$this->intCols = $arrSize[1];
				}
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
	 * Return a parameter
	 *
	 * @param string $strKey The parameter key
	 *
	 * @return mixed The parameter value
	 *
	 * @todo Remove specialchars() from the value in Contao 5 and apply it in the template/generate() method instead
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'cols':
				return $this->intCols;

			case 'rows':
				return $this->intRows;

			case 'value':
				return StringUtil::specialchars(str_replace('\n', "\n", (string) $this->varValue));

			case 'rawValue':
				return $this->varValue;

			default:
				return parent::__get($strKey);
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return sprintf(
			'<textarea name="%s" id="ctrl_%s" class="textarea%s" rows="%s" cols="%s"%s>%s</textarea>',
			$this->strName,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			$this->intRows,
			$this->intCols,
			$this->getAttributes(),
			$this->value
		);
	}
}

class_alias(FormTextArea::class, 'FormTextArea');
