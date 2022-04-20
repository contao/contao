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
 * Class FormPassword
 *
 * @property boolean $mandatory
 * @property integer $maxlength
 * @property string  $placeholder
 * @property string  $confirmLabel
 */
class FormPassword extends Widget
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
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_password';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-password';

	/**
	 * Always use raw request data.
	 *
	 * @param array $arrAttributes An optional attributes array
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);

		$this->useRawRequestData = true;
	}

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

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Validate input and set value
	 *
	 * @param mixed $varInput The user input
	 *
	 * @return mixed The validated user input
	 */
	protected function validator($varInput)
	{
		$this->blnSubmitInput = false;

		if (!\strlen($varInput) && (\strlen($this->varValue) || !$this->mandatory))
		{
			return '';
		}

		// Check password length either from DCA or use Config as fallback (#1087)
		$intLength = $this->minlength ?: Config::get('minPasswordLength');

		if (mb_strlen($varInput) < $intLength)
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['passwordLength'], $intLength));
		}

		$varInput = parent::validator($varInput);

		if (!$this->hasErrors())
		{
			$this->blnSubmitInput = true;

			$passwordHasher = System::getContainer()->get('security.password_hasher_factory')->getPasswordHasher(FrontendUser::class);

			return $passwordHasher->hash($varInput);
		}

		return '';
	}

	/**
	 * Parse the template file and return it as string
	 *
	 * @param array $arrAttributes An optional attributes array
	 *
	 * @return string The template markup
	 */
	public function parse($arrAttributes=null)
	{
		$this->confirmLabel = sprintf($GLOBALS['TL_LANG']['MSC']['confirmation'], $this->strLabel);

		return parent::parse($arrAttributes);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return sprintf(
			'<input type="password" name="%s" id="ctrl_%s" class="text password%s" value="" autocomplete="new-password"%s%s',
			$this->strName,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			$this->strTagEnding
		);
	}

	/**
	 * Generate the label of the confirmation field and return it as string
	 *
	 * @return string The confirmation label markup
	 *
	 * @deprecated Deprecated since Contao 4.12, to be removed in Contao 5.0
	 */
	public function generateConfirmationLabel()
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using "Contao\FormPassword::generateConfirmation()" has been deprecated and will no longer work in Contao 5.0.');

		return sprintf(
			'<label for="ctrl_%s_confirm" class="confirm%s">%s%s%s</label>',
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			($this->mandatory ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : ''),
			sprintf($GLOBALS['TL_LANG']['MSC']['confirmation'], $this->strLabel),
			($this->mandatory ? '<span class="mandatory">*</span>' : '')
		);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The confirmation field markup
	 *
	 * @deprecated Deprecated since Contao 4.12, to be removed in Contao 5.0
	 */
	public function generateConfirmation()
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using "Contao\FormPassword::generateConfirmation()" has been deprecated and will no longer work in Contao 5.0.');

		return sprintf(
			'<input type="password" name="%s_confirm" id="ctrl_%s_confirm" class="text password confirm%s" value="" autocomplete="new-password"%s%s',
			$this->strName,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			$this->strTagEnding
		);
	}
}

class_alias(FormPassword::class, 'FormPassword');
