<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * Class FormCaptcha
 *
 * @property string $name
 * @property string $question
 * @property string $placeholder
 * @property string $rowClass
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FormCaptcha extends \Widget
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
		$this->strCaptchaKey = 'c' . md5(uniqid(mt_rand(), true));
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
				break;

			case 'question':
				return $this->getQuestion();
				break;

			default:
				return parent::__get($strKey);
				break;
		}
	}


	/**
	 * Validate the input and set the value
	 */
	public function validate()
	{
		/** @var SessionInterface $objSession */
		$objSession = \System::getContainer()->get('session');

		$arrCaptcha = $objSession->get('captcha_' . $this->strId);

		if (!\is_array($arrCaptcha) || !\strlen($arrCaptcha['key']) || !\strlen($arrCaptcha['sum']) || \Input::post($arrCaptcha['key']) != $arrCaptcha['sum'] || $arrCaptcha['time'] > (time() - 3) || \Input::post($arrCaptcha['key'].'_name'))
		{
			$this->class = 'error';
			$this->addError($GLOBALS['TL_LANG']['ERR']['captcha']);
		}

		$objSession->set('captcha_' . $this->strId, '');
	}


	/**
	 * Generate the captcha values and store them in the session
	 */
	protected function generateCaptcha()
	{
		if ($this->arrCaptcha) {
			return;
		}

		$int1 = rand(1, 9);
		$int2 = rand(1, 9);

		$this->arrCaptcha = array
		(
			'int1' => $int1,
			'int2' => $int2,
			'sum' => $int1 + $int2,
			'key' => $this->strCaptchaKey,
			'time' => time()
		);

		/** @var SessionInterface $objSession */
		$objSession = \System::getContainer()->get('session');

		$objSession->set('captcha_' . $this->strId, $this->arrCaptcha);
	}


	/**
	 * Generate the captcha question
	 *
	 * @return string The question string
	 */
	protected function getQuestion()
	{
		$this->generateCaptcha();

		$question = $GLOBALS['TL_LANG']['SEC']['question' . rand(1, 3)];
		$question = sprintf($question, $this->arrCaptcha['int1'], $this->arrCaptcha['int2']);

		$strEncoded = '';
		$arrCharacters = Utf8::str_split($question);

		foreach ($arrCharacters as $strCharacter)
		{
			$strEncoded .= sprintf('&#%s;', Utf8::ord($strCharacter));
		}

		return $strEncoded;
	}


	/**
	 * Get the correct sum for the current session
	 *
	 * @return int The sum
	 */
	protected function getSum()
	{
		$this->generateCaptcha();

		return $this->arrCaptcha['sum'];
	}


	/**
	 * Generate the label and return it as string
	 *
	 * @return string The label markup
	 */
	public function generateLabel()
	{
		if ($this->strLabel == '')
		{
			return '';
		}

		return sprintf('<label for="ctrl_%s" class="mandatory%s"><span class="invisible">%s </span>%s<span class="mandatory">*</span><span class="invisible"> %s</span></label>',
						$this->strId,
						(($this->strClass != '') ? ' ' . $this->strClass : ''),
						$GLOBALS['TL_LANG']['MSC']['mandatory'],
						$this->strLabel,
						$this->getQuestion());
	}


	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return sprintf('<input type="text" name="%s" id="ctrl_%s" class="captcha mandatory%s" value="" aria-describedby="captcha_text_%s"%s%s',
						$this->strCaptchaKey,
						$this->strId,
						(($this->strClass != '') ? ' ' . $this->strClass : ''),
						$this->strId,
						$this->getAttributes(),
						$this->strTagEnding);
	}


	/**
	 * Return the captcha question as string
	 *
	 * @return string The question markup
	 */
	public function generateQuestion()
	{
		return sprintf('<span id="captcha_text_%s" class="captcha_text%s">%s</span>',
						$this->strId,
						(($this->strClass != '') ? ' ' . $this->strClass : ''),
						$this->getQuestion());
	}
}
