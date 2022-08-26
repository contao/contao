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
 * Class FormCaptcha
 *
 * @property string $name
 * @property string $question
 * @property string $placeholder
 * @property string $rowClass
 */
class FormCaptcha extends Widget
{
	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_captcha';

	/**
	 * Captcha key
	 *
	 * @var string
	 */
	protected $strCaptchaKey;

	/**
	 * Captcha values
	 *
	 * @var array
	 */
	protected $arrCaptcha = array();

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-captcha mandatory';

	/**
	 * Initialize the object
	 *
	 * @param array $arrAttributes An optional attributes array
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);

		$this->arrAttributes['maxlength'] = 2;
		$this->strCaptchaKey = 'captcha_' . $this->strId;
		$this->arrAttributes['required'] = true;
		$this->arrConfiguration['mandatory'] = true;
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
			case 'placeholder':
				$this->arrAttributes['placeholder'] = $varValue;
				break;

			case 'required':
			case 'mandatory':
			case 'minlength':
			case 'maxlength':
				// Ignore
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
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'name':
				return $this->strCaptchaKey;

			case 'question':
				return $this->getQuestion();

			default:
				return parent::__get($strKey);
		}
	}

	/**
	 * Validate the input and set the value
	 */
	public function validate()
	{
		if (!isset($_POST[$this->strCaptchaKey]) || (isset($_POST[$this->strCaptchaKey . '_name']) && Input::post($this->strCaptchaKey . '_name')) || !\in_array(Input::post($this->strCaptchaKey . '_hash'), $this->generateHashes((int) Input::post($this->strCaptchaKey)), true))
		{
			$this->class = 'error';
			$this->addError($GLOBALS['TL_LANG']['ERR']['captcha']);
		}
	}

	/**
	 * Generate the captcha values
	 */
	protected function generateCaptcha()
	{
		if ($this->arrCaptcha)
		{
			return;
		}

		$int1 = random_int(1, 9);
		$int2 = random_int(1, 9);

		$this->arrCaptcha = array
		(
			'int1' => $int1,
			'int2' => $int2,
			'sum' => $int1 + $int2,
			'key' => $this->strCaptchaKey,
			'hashes' => $this->generateHashes($int1 + $int2)
		);
	}

	/**
	 * Generate hashes for the current time and the specified sum
	 *
	 * @param integer $sum
	 *
	 * @return array
	 */
	protected function generateHashes($sum)
	{
		// Round the time to 30 minutes
		$time = (int) round(time() / 60 / 30);

		return array_map(
			static function ($hashTime) use ($sum)
			{
				return hash_hmac('sha256', $sum . "\0" . $hashTime, System::getContainer()->getParameter('kernel.secret'));
			},
			array($time, $time - 1)
		);
	}

	/**
	 * Generate the captcha question
	 *
	 * @return string The question string
	 */
	protected function getQuestion()
	{
		$this->generateCaptcha();

		$question = $GLOBALS['TL_LANG']['SEC']['question' . random_int(1, 3)];
		$question = sprintf($question, $this->arrCaptcha['int1'], $this->arrCaptcha['int2']);

		$strEncoded = '';
		$arrCharacters = mb_str_split($question);

		foreach ($arrCharacters as $index => $strCharacter)
		{
			$strEncoded .= sprintf(($index % 2) ? '&#x%X;' : '&#%s;', mb_ord($strCharacter));
		}

		return $strEncoded;
	}

	/**
	 * Get the correct sum for the current captcha
	 *
	 * @return int The sum
	 */
	protected function getSum()
	{
		$this->generateCaptcha();

		return $this->arrCaptcha['sum'];
	}

	/**
	 * Get the correct hash for the current captcha
	 *
	 * @return string The hash
	 */
	protected function getHash()
	{
		$this->generateCaptcha();

		return $this->arrCaptcha['hashes'][0];
	}

	/**
	 * Generate the label and return it as string
	 *
	 * @return string The label markup
	 */
	public function generateLabel()
	{
		if (!$this->strLabel)
		{
			return '';
		}

		return sprintf(
			'<label for="ctrl_%s" class="mandatory%s"><span class="invisible">%s </span>%s<span class="mandatory">*</span><span class="invisible"> %s</span></label>',
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			$GLOBALS['TL_LANG']['MSC']['mandatory'],
			$this->strLabel,
			$this->getQuestion()
		);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return sprintf(
			'<input type="text" name="%s" id="ctrl_%s" class="captcha mandatory%s" value="" aria-describedby="captcha_text_%s"%s%s',
			$this->strCaptchaKey,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			$this->strId,
			$this->getAttributes(),
			$this->strTagEnding
		);
	}

	/**
	 * Return the captcha question as string
	 *
	 * @return string The question markup
	 */
	public function generateQuestion()
	{
		return sprintf(
			'<span id="captcha_text_%s" class="captcha_text%s">%s</span>',
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			$this->getQuestion()
		);
	}
}

class_alias(FormCaptcha::class, 'FormCaptcha');
