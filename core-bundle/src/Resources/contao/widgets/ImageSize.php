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
 * Provide methods to handle image size fields.
 *
 * @property integer $maxlength
 * @property array   $options
 * @property array   $unknownOption
 */
class ImageSize extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Available options
	 * @var array
	 */
	protected $arrAvailableOptions = array();

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

			case 'options':
				$this->arrOptions = StringUtil::deserialize($varValue);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Trim values
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		$varInput[2] = preg_replace('/[^a-z0-9_]+/', '', $varInput[2] ?? '');

		if (!is_numeric($varInput[2]) && strpos($varInput[2], '_') !== 0)
		{
			switch ($varInput[2])
			{
				// Validate relative dimensions - width or height required
				case 'proportional':
				case 'box':
					$this->mandatory = !$varInput[0] && !$varInput[1];
					break;

				// Validate exact dimensions - width and height required
				case 'crop':
				case 'left_top':
				case 'center_top':
				case 'right_top':
				case 'left_center':
				case 'center_center':
				case 'right_center':
				case 'left_bottom':
				case 'center_bottom':
				case 'right_bottom':
					$this->mandatory = !$varInput[0] || !$varInput[1];
					break;
			}

			$varInput[0] = parent::validator($varInput[0]);
			$varInput[1] = parent::validator($varInput[1]);
		}

		$this->import(BackendUser::class, 'User');

		$imageSizes = System::getContainer()->get('contao.image.sizes');
		$this->arrAvailableOptions = $this->User->isAdmin ? $imageSizes->getAllOptions() : $imageSizes->getOptionsForUser($this->User);

		if (!$this->isValidOption($varInput[2]))
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalid'], $varInput[2]));
		}

		return $varInput;
	}

	/**
	 * Check whether an input is one of the given options
	 *
	 * @param mixed $varInput The input string or array
	 *
	 * @return boolean True if the selected option exists
	 */
	protected function isValidOption($varInput)
	{
		if (!$varInput)
		{
			return true;
		}

		foreach ($this->arrAvailableOptions as $strGroup=>$arrValues)
		{
			if ($strGroup == 'custom' || $strGroup == 'relative' || $strGroup == 'exact')
			{
				if (\in_array($varInput, $arrValues))
				{
					return true;
				}
			}
			elseif (isset($arrValues[$varInput]))
			{
				return true;
			}
		}

		if (isset($this->unknownOption[2]) && $varInput == $this->unknownOption[2])
		{
			return true;
		}

		return false;
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		if (!\is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}

		// Handle the old image format (see #795)
		if ($this->varValue == array('', '', 'proportional'))
		{
			$this->varValue = array('', '', '');
		}

		$arrFields = array();
		$arrOptions = array();
		$arrAllOptions = $this->arrOptions;

		// Add an unknown option, so it is not lost when saving the record (see #920)
		if (isset($this->unknownOption[2]))
		{
			$arrAllOptions[] = array('value' => $this->unknownOption[2], 'label' => sprintf($GLOBALS['TL_LANG']['MSC']['unknownOption'], $this->unknownOption[2]));
		}

		foreach ($arrAllOptions as $strKey=>$arrOption)
		{
			if (isset($arrOption['value']))
			{
				$arrOptions[] = sprintf(
					'<option value="%s"%s>%s</option>',
					self::specialcharsValue($arrOption['value'] ?? ''),
					$this->optionSelected($arrOption['value'] ?? null, $this->varValue[2] ?? null),
					$arrOption['label'] ?? null
				);
			}
			else
			{
				$arrOptgroups = array();

				foreach ($arrOption as $arrOptgroup)
				{
					$arrOptgroups[] = sprintf(
						'<option value="%s"%s>%s</option>',
						self::specialcharsValue($arrOptgroup['value'] ?? ''),
						$this->optionSelected($arrOptgroup['value'] ?? null, $this->varValue[2] ?? null),
						$arrOptgroup['label'] ?? null
					);
				}

				$arrOptions[] = sprintf('<optgroup label="&nbsp;%s">%s</optgroup>', StringUtil::specialchars($strKey), implode('', $arrOptgroups));
			}
		}

		$arrFields[] = sprintf(
			'<select name="%s[2]" id="ctrl_%s" class="tl_select_interval" onfocus="Backend.getScrollOffset()"%s>%s</select>',
			$this->strName,
			$this->strId . '_3',
			$this->getAttribute('disabled'),
			implode(' ', $arrOptions)
		);

		for ($i=0; $i<2; $i++)
		{
			$arrFields[] = sprintf(
				'<input type="text" name="%s[%s]" id="ctrl_%s" class="tl_text_4 tl_imageSize_%s" value="%s"%s onfocus="Backend.getScrollOffset()">',
				$this->strName,
				$i,
				$this->strId . '_' . $i,
				$i,
				self::specialcharsValue(@$this->varValue[$i]), // see #4979
				$this->getAttributes()
			);
		}

		return sprintf(
			'<div id="ctrl_%s" class="tl_image_size%s">%s</div>%s',
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			implode(' ', $arrFields),
			$this->wizard
		);
	}
}
