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
 * Provide methods to handle password fields.
 *
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property string  $placeholder
 * @property string  $description
 */
class Password extends Widget
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
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Always use raw request data.
	 *
	 * @param array $arrAttributes
	 */
	public function __construct($arrAttributes=null)
	{
		// If there is a password, the field is no longer mandatory
		if (!empty($arrAttributes['value']))
		{
			$arrAttributes['mandatory'] = false;
		}

		parent::__construct($arrAttributes);

		$this->useRawRequestData = true;
	}

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

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Validate input and set value
	 *
	 * @param mixed $varInput
	 *
	 * @return string
	 */
	protected function validator($varInput)
	{
		$this->blnSubmitInput = false;

		if (!$varInput && $this->varValue)
		{
			return '';
		}

		// Check password length either from DCA or use Config as fallback (#1086)
		$intLength = $this->minlength ?: Config::get('minPasswordLength');

		if (mb_strlen($varInput) < $intLength)
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['passwordLength'], $intLength));
		}

		if (isset($GLOBALS['TL_USERNAME']) && $varInput == $GLOBALS['TL_USERNAME'])
		{
			$this->addError($GLOBALS['TL_LANG']['ERR']['passwordName']);
		}

		$varInput = parent::validator($varInput);

		if (!$this->hasErrors())
		{
			$this->blnSubmitInput = true;

			$passwordHasher = System::getContainer()->get('security.password_hasher_factory')->getPasswordHasher(BackendUser::class);

			return $passwordHasher->hash($varInput);
		}

		return '';
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		return sprintf(
			'<input type="password" name="%s" id="ctrl_%s" class="tl_text tl_password%s" value="" placeholder="%s" autocomplete="new-password"%s onfocus="Backend.getScrollOffset()">%s%s',
			$this->strName,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			($this->varValue ? '*****' : ''),
			$this->getAttributes(),
			$this->wizard,
			(($this->description && Config::get('showHelp') && !$this->hasErrors()) ? "\n  " . '<p class="tl_help tl_tip">' . $this->description . '</p>' : '')
		);
	}

	/**
	 * Generate the label of the confirmation field and return it as string
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.12, to be removed in Contao 5.0
	 */
	public function generateConfirmationLabel()
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using "Contao\Password::generateConfirmationLabel()" has been deprecated and will no longer work in Contao 5.0.');

		return sprintf(
			'<label for="ctrl_%s_confirm" class="confirm%s">%s%s%s</label>',
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			($this->mandatory ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : ''),
			$GLOBALS['TL_LANG']['MSC']['confirm'][0],
			($this->mandatory ? '<span class="mandatory">*</span>' : '')
		);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.12, to be removed in Contao 5.0
	 */
	public function generateConfirmation()
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using "Contao\Password::generateConfirmation()" has been deprecated and will no longer work in Contao 5.0.');

		return sprintf(
			'<input type="password" name="%s_confirm" id="ctrl_%s_confirm" class="tl_text tl_password confirm%s" value="" placeholder="%s" autocomplete="new-password"%s onfocus="Backend.getScrollOffset()">%s',
			$this->strName,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			($this->varValue ? '*****' : ''),
			$this->getAttributes(),
			((isset($GLOBALS['TL_LANG']['MSC']['confirm'][1]) && Config::get('showHelp')) ? "\n  " . '<p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['MSC']['confirm'][1] . '</p>' : '')
		);
	}
}

class_alias(Password::class, 'Password');
