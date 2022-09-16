<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Database\Result;
use Doctrine\DBAL\Types\Types;

/**
 * Generates and validates form fields
 *
 * The class functions as abstract parent class for all widget classes and
 * provides methods to generate the form field markup and to validate the form
 * field input.
 *
 * Usage:
 *
 *     $widget = new TextField();
 *     $widget->name = 'test';
 *     $widget->label = 'Test';
 *
 *     if ($_POST)
 *     {
 *         $widget->validate();
 *
 *         if (!$widget->hasErrors())
 *         {
 *             echo $widget->value;
 *         }
 *     }
 *
 * @property string        $id                 The field ID
 * @property string        $name               the field name
 * @property string        $label              The field label
 * @property mixed         $value              The field value
 * @property string        $class              One or more CSS classes
 * @property string        $prefix             The CSS class prefix
 * @property string        $template           The template name
 * @property string        $wizard             The field wizard markup
 * @property string        $alt                The alternative text
 * @property string        $style              The style attribute
 * @property string        $accesskey          The key to focus the field
 * @property boolean       $disabled           Adds the disabled attribute
 * @property boolean       $readonly           Adds the readonly attribute
 * @property boolean       $autofocus          Adds the autofocus attribute
 * @property boolean       $required           Adds the required attribute
 * @property string        $onblur             The blur event
 * @property string        $onchange           The change event
 * @property string        $onclick            The click event
 * @property string        $ondblclick         The double click event
 * @property string        $onfocus            The focus event
 * @property string        $onmousedown        The mouse down event
 * @property string        $onmousemove        The mouse move event
 * @property string        $onmouseout         The mouse out event
 * @property string        $onmouseover        The mouse over event
 * @property string        $onmouseup          The mouse up event
 * @property string        $onkeydown          The key down event
 * @property string        $onkeypress         The key press event
 * @property string        $onkeyup            The key up event
 * @property string        $onselect           The select event
 * @property boolean       $mandatory          The field value must not be empty
 * @property boolean       $nospace            Do not allow whitespace characters
 * @property boolean       $allowHtml          Allow HTML tags in the field value
 * @property boolean       $storeFile          Store uploaded files in a given folder
 * @property boolean       $useHomeDir         Store uploaded files in the user's home directory
 * @property boolean       $trailingSlash      Add or remove a trailing slash
 * @property boolean       $spaceToUnderscore  Convert spaces to underscores
 * @property boolean       $doNotTrim          Do not trim the user input
 * @property string        $forAttribute       The "for" attribute
 * @property DataContainer $dataContainer      The data container object
 * @property Result        $activeRecord       The active record
 * @property string        $mandatoryField     The "mandatory field" label
 * @property string        $customTpl          A custom template name
 * @property string        $slabel             The submit button label
 * @property boolean       $preserveTags       Preserve HTML tags
 * @property boolean       $decodeEntities     Decode HTML entities
 * @property boolean       $useRawRequestData  Use the raw request data from the Symfony request
 * @property integer       $minlength          The minimum length
 * @property integer       $maxlength          The maximum length
 * @property integer       $minval             The minimum value
 * @property integer       $maxval             The maximum value
 * @property integer       $rgxp               The regular expression name
 * @property boolean       $isHexColor         The field value is a hex color
 * @property string        $strTable           The table name
 * @property string        $strField           The field name
 * @property string        $xlabel
 * @property string        $customRgxp
 * @property string        $errorMsg
 * @property integer       $currentRecord
 * @property integer       $storeValues
 * @property boolean       $includeBlankOption
 * @property string        $blankOptionLabel
 * @property boolean       $basicEntities
 */
abstract class Widget extends Controller
{
	use TemplateInheritance;

	/**
	 * Id
	 * @var integer
	 */
	protected $strId;

	/**
	 * Name
	 * @var string
	 */
	protected $strName;

	/**
	 * Label
	 * @var string
	 */
	protected $strLabel;

	/**
	 * Value
	 * @var mixed
	 */
	protected $varValue;

	/**
	 * Input callback
	 * @var callable
	 */
	protected $inputCallback;

	/**
	 * CSS class
	 * @var string
	 */
	protected $strClass;

	/**
	 * CSS class prefix
	 * @var string
	 */
	protected $strPrefix;

	/**
	 * Wizard
	 * @var string
	 */
	protected $strWizard;

	/**
	 * Errors
	 * @var array
	 */
	protected $arrErrors = array();

	/**
	 * Attributes
	 * @var array
	 */
	protected $arrAttributes = array();

	/**
	 * Configuration
	 * @var array
	 */
	protected $arrConfiguration = array();

	/**
	 * Options
	 * @var array
	 */
	protected $arrOptions = array();

	/**
	 * Submit indicator
	 * @var boolean
	 */
	protected $blnSubmitInput = false;

	/**
	 * For attribute indicator
	 * @var boolean
	 */
	protected $blnForAttribute = false;

	/**
	 * Data container
	 * @var object
	 */
	protected $objDca;

	/**
	 * Initialize the object
	 *
	 * @param array $arrAttributes An optional attributes array
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct();

		$this->addAttributes($arrAttributes);
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'id':
				$this->strId = $varValue;
				break;

			case 'name':
				$this->strName = $varValue;
				break;

			case 'label':
				$this->strLabel = $varValue;
				break;

			case 'value':
				$this->varValue = StringUtil::deserialize($varValue);
				break;

			case 'class':
				if ($varValue && strpos($this->strClass ?? '', $varValue) === false)
				{
					$this->strClass = trim($this->strClass . ' ' . $varValue);
				}
				break;

			case 'prefix':
				$this->strPrefix = $varValue;
				break;

			case 'template':
				$this->strTemplate = $varValue;
				break;

			case 'wizard':
				$this->strWizard = $varValue;
				break;

			case 'autocomplete':
			case 'autocorrect':
			case 'autocapitalize':
			case 'spellcheck':
				if (\is_bool($varValue))
				{
					$varValue = $varValue ? 'on' : 'off';
				}
				// no break

			case 'alt':
			case 'style':
			case 'accesskey':
			case 'onblur':
			case 'onchange':
			case 'onclick':
			case 'ondblclick':
			case 'onfocus':
			case 'onmousedown':
			case 'onmousemove':
			case 'onmouseout':
			case 'onmouseover':
			case 'onmouseup':
			case 'onkeydown':
			case 'onkeypress':
			case 'onkeyup':
			case 'onselect':
				$this->arrAttributes[$strKey] = $varValue;
				break;

			case 'disabled':
			case 'readonly':
				$this->blnSubmitInput = $varValue ? false : true;
				// no break

			case 'autofocus':
				if ($varValue)
				{
					$this->arrAttributes[$strKey] = $strKey;
				}
				else
				{
					unset($this->arrAttributes[$strKey]);
				}
				break;

			case 'required':
				if ($varValue)
				{
					$this->strClass = trim($this->strClass . ' mandatory');
				}
				// no break

			case 'mandatory':
			case 'nospace':
			case 'allowHtml':
			case 'storeFile':
			case 'useHomeDir':
			case 'storeValues':
			case 'trailingSlash':
			case 'spaceToUnderscore':
			case 'doNotTrim':
			case 'useRawRequestData':
				$this->arrConfiguration[$strKey] = $varValue ? true : false;
				break;

			case 'forAttribute':
				$this->blnForAttribute = $varValue;
				break;

			case 'dataContainer':
				$this->objDca = $varValue;
				break;

			case strncmp($strKey, 'ng-', 3) === 0:
			case strncmp($strKey, 'data-', 5) === 0:
				$this->arrAttributes[$strKey] = $varValue;
				break;

			default:
				$this->arrConfiguration[$strKey] = $varValue;
				break;
		}
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return string The property value
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'id':
				return $this->strId;

			case 'name':
				return $this->strName;

			case 'label':
				return $this->strLabel;

			case 'value':
				if ($this->varValue === '')
				{
					return $this->getEmptyStringOrNull();
				}

				if ($this->basicEntities)
				{
					return StringUtil::restoreBasicEntities($this->varValue);
				}

				return $this->varValue;

			case 'class':
				return $this->strClass;

			case 'prefix':
				return $this->strPrefix;

			case 'template':
				return $this->strTemplate;

			case 'wizard':
				return $this->strWizard;

			case 'required':
				return $this->arrConfiguration[$strKey];

			case 'forAttribute':
				return $this->blnForAttribute;

			case 'dataContainer':
				return $this->objDca;

			case 'activeRecord':
				return $this->objDca->activeRecord;

			default:
				if (isset($this->arrAttributes[$strKey]))
				{
					return $this->arrAttributes[$strKey];
				}

				if (isset($this->arrConfiguration[$strKey]))
				{
					return $this->arrConfiguration[$strKey];
				}
				break;
		}

		return parent::__get($strKey);
	}

	/**
	 * Check whether an object property exists
	 *
	 * @param string $strKey The property name
	 *
	 * @return boolean True if the property exists
	 */
	public function __isset($strKey)
	{
		switch ($strKey)
		{
			case 'id':
				return isset($this->strId);

			case 'name':
				return isset($this->strName);

			case 'label':
				return isset($this->strLabel);

			case 'value':
				return isset($this->varValue);

			case 'class':
				return isset($this->strClass);

			case 'template':
				return isset($this->strTemplate);

			case 'wizard':
				return isset($this->strWizard);

			case 'required':
				return isset($this->arrConfiguration[$strKey]);

			case 'forAttribute':
				return isset($this->blnForAttribute);

			case 'dataContainer':
				return isset($this->objDca);

			case 'activeRecord':
				return isset($this->objDca->activeRecord);

			default:
				return isset($this->arrAttributes[$strKey]) || isset($this->arrConfiguration[$strKey]);
		}
	}

	/**
	 * Add an attribute
	 *
	 * @param string $strName  The attribute name
	 * @param mixed  $varValue The attribute value
	 */
	public function addAttribute($strName, $varValue)
	{
		$this->arrAttributes[$strName] = $varValue;
	}

	/**
	 * Add an error message
	 *
	 * @param string $strError The error message
	 */
	public function addError($strError)
	{
		$this->class = 'error';
		$this->arrErrors[] = $strError;
	}

	/**
	 * Return true if the widget has errors
	 *
	 * @return boolean True if there are errors
	 */
	public function hasErrors()
	{
		return !empty($this->arrErrors);
	}

	/**
	 * Return the errors array
	 *
	 * @return array An array of error messages
	 */
	public function getErrors()
	{
		return $this->arrErrors;
	}

	/**
	 * Return a particular error as string
	 *
	 * @param integer $intIndex The message index
	 *
	 * @return string The corresponding error message
	 */
	public function getErrorAsString($intIndex=0)
	{
		return $this->arrErrors[$intIndex];
	}

	/**
	 * Return all errors as string separated by a given separator
	 *
	 * @param string $strSeparator An optional separator (defaults to "<br>")
	 *
	 * @return string The error messages string
	 */
	public function getErrorsAsString($strSeparator=null)
	{
		if ($strSeparator === null)
		{
			$strSeparator = '<br' . $this->strTagEnding . "\n";
		}

		return $this->hasErrors() ? implode($strSeparator, $this->arrErrors) : '';
	}

	/**
	 * Return a particular error as HTML string
	 *
	 * @param integer $intIndex The message index
	 *
	 * @return string The HTML markup of the corresponding error message
	 */
	public function getErrorAsHTML($intIndex=0)
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();
		$isBackend = $request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request);

		return $this->hasErrors() ? sprintf('<p class="%s">%s</p>', ($isBackend ? 'tl_error tl_tip' : 'error'), $this->arrErrors[$intIndex]) : '';
	}

	/**
	 * Return true if the widgets submits user input
	 *
	 * @return boolean True if the widget submits user input
	 */
	public function submitInput()
	{
		return $this->blnSubmitInput;
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
		if (!$this->strTemplate)
		{
			return '';
		}

		$this->addAttributes($arrAttributes);

		$this->mandatoryField = $GLOBALS['TL_LANG']['MSC']['mandatory'];

		if ($this->customTpl)
		{
			$request = System::getContainer()->get('request_stack')->getCurrentRequest();

			// Use the custom template unless it is a back end request
			if (!$request || !System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
			{
				$this->strTemplate = $this->customTpl;
			}
		}

		$strBuffer = $this->inherit();

		// HOOK: add custom parse filters (see #5553)
		if (isset($GLOBALS['TL_HOOKS']['parseWidget']) && \is_array($GLOBALS['TL_HOOKS']['parseWidget']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseWidget'] as $callback)
			{
				$this->import($callback[0]);
				$strBuffer = $this->{$callback[0]}->{$callback[1]}($strBuffer, $this);
			}
		}

		return $strBuffer;
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
			'<label%s%s>%s%s%s</label>',
			($this->blnForAttribute ? ' for="ctrl_' . $this->strId . '"' : ''),
			($this->strClass ? ' class="' . $this->strClass . '"' : ''),
			($this->mandatory ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : ''),
			$this->strLabel,
			($this->mandatory ? '<span class="mandatory">*</span>' : '')
		);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	abstract public function generate();

	/**
	 * Generate the widget with error message and return it as string
	 *
	 * @param boolean $blnSwitchOrder If true, the error message will be shown below the field
	 *
	 * @return string The form field markup
	 */
	public function generateWithError($blnSwitchOrder=false)
	{
		$strWidget = $this->generate();
		$strError = $this->getErrorAsHTML();

		return $blnSwitchOrder ? $strWidget . $strError : $strError . $strWidget;
	}

	/**
	 * Return all attributes as string
	 *
	 * @param array $arrStrip An optional array with attributes to strip
	 *
	 * @return string The attributes string
	 */
	public function getAttributes($arrStrip=array())
	{
		$strAttributes = '';

		foreach (array_keys($this->arrAttributes) as $strKey)
		{
			if (!\in_array($strKey, $arrStrip))
			{
				$strAttributes .= $this->getAttribute($strKey);
			}
		}

		return $strAttributes;
	}

	/**
	 * Return a single attribute
	 *
	 * @param string $strKey The attribute name
	 *
	 * @return string The attribute markup
	 */
	public function getAttribute($strKey)
	{
		if (!isset($this->arrAttributes[$strKey]))
		{
			return '';
		}

		$varValue = $this->arrAttributes[$strKey];

		// Prevent the autofocus attribute from being added multiple times (see #8281)
		if ($strKey == 'autofocus')
		{
			unset($this->arrAttributes[$strKey]);
		}

		if ($strKey == 'disabled' || $strKey == 'readonly' || $strKey == 'required' || $strKey == 'autofocus' || $strKey == 'multiple')
		{
			return ' ' . $strKey;
		}

		if ('' !== (string) $varValue)
		{
			return ' ' . $strKey . '="' . StringUtil::specialchars($varValue) . '"';
		}

		return '';
	}

	/**
	 * Set a callback to fetch the widget input instead of using getPost()
	 *
	 * @param callable|null $callback The callback
	 *
	 * @return $this The widget object
	 */
	public function setInputCallback(callable $callback=null)
	{
		$this->inputCallback = $callback;

		return $this;
	}

	/**
	 * Validate the user input and set the value
	 */
	public function validate()
	{
		$varValue = $this->validator($this->getPost($this->strName));

		if ($this->hasErrors())
		{
			$this->class = 'error';
		}

		$this->varValue = $varValue;
	}

	/**
	 * Find and return a $_POST variable
	 *
	 * @param string $strKey The variable name
	 *
	 * @return mixed The variable value
	 */
	protected function getPost($strKey)
	{
		if (\is_callable($this->inputCallback))
		{
			return ($this->inputCallback)();
		}

		if ($this->useRawRequestData === true)
		{
			$request = System::getContainer()->get('request_stack')->getCurrentRequest();

			return $request->request->get($strKey);
		}

		$strMethod = $this->allowHtml ? 'postHtml' : 'post';

		if ($this->preserveTags)
		{
			$strMethod = 'postRaw';
		}

		// Support arrays (thanks to Andreas Schempp)
		$arrParts = explode('[', str_replace(']', '', (string) $strKey));
		$varValue = Input::$strMethod(array_shift($arrParts), $this->decodeEntities);

		foreach ($arrParts as $part)
		{
			if (!\is_array($varValue))
			{
				break;
			}

			$varValue = $varValue[$part] ?? null;
		}

		return $varValue;
	}

	/**
	 * Recursively validate an input variable
	 *
	 * @param mixed $varInput The user input
	 *
	 * @return mixed The original or modified user input
	 */
	protected function validator($varInput)
	{
		if (\is_array($varInput))
		{
			foreach ($varInput as $k=>$v)
			{
				$varInput[$k] = $this->validator($v);
			}

			return $varInput;
		}

		if (!$this->doNotTrim && \is_string($varInput))
		{
			$varInput = trim($varInput);
		}

		if ((string) $varInput === '')
		{
			if (!$this->mandatory)
			{
				return '';
			}

			if (!$this->strLabel)
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['mdtryNoLabel']);
			}
			else
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
			}
		}

		if ($this->minlength && $varInput && mb_strlen($varInput) < $this->minlength)
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['minlength'], $this->strLabel, $this->minlength));
		}

		if ($this->maxlength && $varInput && mb_strlen($varInput) > $this->maxlength)
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['maxlength'], $this->strLabel, $this->maxlength));
		}

		if ($this->minval && is_numeric($varInput) && $varInput < $this->minval)
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['minval'], $this->strLabel, $this->minval));
		}

		if ($this->maxval && is_numeric($varInput) && $varInput > $this->maxval)
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['maxval'], $this->strLabel, $this->maxval));
		}

		if ($this->rgxp)
		{
			switch ($this->rgxp)
			{
				case strncmp($this->rgxp, 'digit_', 6) === 0:
					// Special validation rule for style sheets
					$textual = explode('_', $this->rgxp);
					array_shift($textual);

					if (\in_array($varInput, $textual) || strncmp($varInput, '$', 1) === 0)
					{
						break;
					}
					// no break

				case 'digit':
					// Support decimal commas and convert them automatically (see #3488)
					if (substr_count($varInput, ',') == 1 && strpos($varInput, '.') === false)
					{
						$varInput = str_replace(',', '.', $varInput);
					}

					if (!Validator::isNumeric($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['digit'], $this->strLabel));
					}
					break;

				case 'natural':
					if (!Validator::isNatural($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['natural'], $this->strLabel));
					}
					break;

				case 'alpha':
					if (!Validator::isAlphabetic($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alpha'], $this->strLabel));
					}
					break;

				case 'alnum':
					if (!Validator::isAlphanumeric($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $this->strLabel));
					}
					break;

				case 'extnd':
					if (!Validator::isExtendedAlphanumeric(html_entity_decode($varInput)))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['extnd'], $this->strLabel));
					}
					break;

				case 'date':
					if (!Validator::isDate($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['date'], Date::getInputFormat(Date::getNumericDateFormat())));
					}
					else
					{
						// Validate the date (see #5086)
						try
						{
							new Date($varInput, Date::getNumericDateFormat());
						}
						catch (\OutOfBoundsException $e)
						{
							$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'], $varInput));
						}
					}
					break;

				case 'time':
					if (!Validator::isTime($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['time'], Date::getInputFormat(Date::getNumericTimeFormat())));
					}
					break;

				case 'datim':
					if (!Validator::isDatim($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['dateTime'], Date::getInputFormat(Date::getNumericDatimFormat())));
					}
					else
					{
						// Validate the date (see #5086)
						try
						{
							new Date($varInput, Date::getNumericDatimFormat());
						}
						catch (\OutOfBoundsException $e)
						{
							$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'], $varInput));
						}
					}
					break;

				case 'friendly':
					list ($strName, $varInput) = StringUtil::splitFriendlyEmail($varInput);
					// no break

				case 'email':
					if (!Validator::isEmail($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['email'], $this->strLabel));
					}

					if ($this->rgxp == 'friendly' && !empty($strName))
					{
						$varInput = $strName . ' [' . $varInput . ']';
					}
					break;

				case 'emails':
					// Check whether the current value is list of valid e-mail addresses
					$arrEmails = StringUtil::trimsplit(',', $varInput);

					foreach ($arrEmails as $strEmail)
					{
						$strEmail = Idna::encodeEmail($strEmail);

						if (!Validator::isEmail($strEmail))
						{
							$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['emails'], $this->strLabel));
							break;
						}
					}
					break;

				case 'url':
					$varInput = StringUtil::specialcharsUrl($varInput);

					if ($this->decodeEntities)
					{
						$varInput = StringUtil::decodeEntities($varInput);
					}

					if (!Validator::isUrl($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['url'], $this->strLabel));
					}
					break;

				case 'alias':
					if (!Validator::isAlias($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alias'], $this->strLabel));
					}
					break;

				case 'folderalias':
					if (!Validator::isFolderAlias($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['folderalias'], $this->strLabel));
					}
					break;

				case 'phone':
					if (!Validator::isPhone(html_entity_decode($varInput)))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['phone'], $this->strLabel));
					}
					break;

				case 'prcnt':
					if (!Validator::isPercent($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['prcnt'], $this->strLabel));
					}
					break;

				case 'locale':
					if (!Validator::isLocale($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['locale'], $this->strLabel));
					}
					break;

				case 'language':
					if (!Validator::isLanguage($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['language'], $this->strLabel));
					}
					break;

				case 'fieldname':
					if (!Validator::isFieldName($varInput))
					{
						$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidFieldName'], $this->strLabel));
					}
					break;

				// HOOK: pass unknown tags to callback functions
				default:
					if (isset($GLOBALS['TL_HOOKS']['addCustomRegexp']) && \is_array($GLOBALS['TL_HOOKS']['addCustomRegexp']))
					{
						foreach ($GLOBALS['TL_HOOKS']['addCustomRegexp'] as $callback)
						{
							$this->import($callback[0]);
							$break = $this->{$callback[0]}->{$callback[1]}($this->rgxp, $varInput, $this);

							// Stop the loop if a callback returned true
							if ($break === true)
							{
								break;
							}
						}
					}
					break;
			}
		}

		if ($this->isHexColor && $varInput && strncmp($varInput, '$', 1) !== 0)
		{
			$varInput = preg_replace('/[^a-f0-9]+/i', '', $varInput);
		}

		if ($this->nospace && preg_match('/[\t ]+/', $varInput))
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['noSpace'], $this->strLabel));
		}

		if ($this->spaceToUnderscore)
		{
			$varInput = preg_replace('/\s+/', '_', trim($varInput));
		}

		if (\is_bool($this->trailingSlash) && $varInput)
		{
			$varInput = preg_replace('/\/+$/', '', $varInput) . ($this->trailingSlash ? '/' : '');
		}

		return $varInput;
	}

	/**
	 * Take an associative array and add it to the object's attributes
	 *
	 * @param array $arrAttributes An array of attributes
	 */
	public function addAttributes($arrAttributes)
	{
		if (!\is_array($arrAttributes))
		{
			return;
		}

		foreach ($arrAttributes as $k=>$v)
		{
			$this->$k = $v;
		}
	}

	/**
	 * Check whether an option is checked
	 *
	 * @param array $arrOption The options array
	 *
	 * @return string The "checked" attribute or an empty string
	 */
	protected function isChecked($arrOption)
	{
		if (empty($this->varValue) && !Input::isPost() && ($arrOption['default'] ?? null))
		{
			return static::optionChecked(1, 1);
		}

		return static::optionChecked($arrOption['value'] ?? null, $this->varValue);
	}

	/**
	 * Check whether an option is selected
	 *
	 * @param array $arrOption The options array
	 *
	 * @return string The "selected" attribute or an empty string
	 */
	protected function isSelected($arrOption)
	{
		if (empty($this->varValue) && !Input::isPost() && ($arrOption['default'] ?? null))
		{
			return static::optionSelected(1, 1);
		}

		return static::optionSelected($arrOption['value'] ?? null, $this->varValue);
	}

	/**
	 * Return a "selected" attribute if the option is selected
	 *
	 * @param string $strOption The option to check
	 * @param mixed  $varValues One or more values to check against
	 *
	 * @return string The attribute or an empty string
	 */
	public static function optionSelected($strOption, $varValues)
	{
		if ($strOption === '')
		{
			return '';
		}

		return (\is_array($varValues) ? \in_array($strOption, $varValues) : $strOption == $varValues) ? ' selected' : '';
	}

	/**
	 * Return a "checked" attribute if the option is checked
	 *
	 * @param string $strOption The option to check
	 * @param mixed  $varValues One or more values to check against
	 *
	 * @return string The attribute or an empty string
	 */
	public static function optionChecked($strOption, $varValues)
	{
		if ($strOption === '')
		{
			return '';
		}

		return (\is_array($varValues) ? \in_array($strOption, $varValues) : $strOption == $varValues) ? ' checked' : '';
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
		if (!\is_array($varInput))
		{
			$varInput = array($varInput);
		}

		// Check each option
		foreach ($varInput as $strInput)
		{
			$blnFound = false;

			foreach ($this->arrOptions as $v)
			{
				// Single dimensional array
				if (\array_key_exists('value', $v))
				{
					if ($strInput == $v['value'])
					{
						$blnFound = true;
					}
				}
				// Multi-dimensional array
				else
				{
					foreach ($v as $vv)
					{
						if ($strInput == $vv['value'])
						{
							$blnFound = true;
						}
					}
				}
			}

			if (!$blnFound)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Extract the Widget attributes from a Data Container array
	 *
	 * @param array                     $arrData  The field configuration array
	 * @param string                    $strName  The field name in the form
	 * @param mixed                     $varValue The field value
	 * @param string                    $strField The field name in the database
	 * @param string                    $strTable The table name in the database
	 * @param DataContainer|Module|null $objDca   An optional DataContainer or Module object
	 *
	 * @return array An attributes array that can be passed to a widget
	 */
	public static function getAttributesFromDca($arrData, $strName, $varValue=null, $strField='', $strTable='', $objDca=null)
	{
		$arrAttributes = $arrData['eval'] ?? array();

		if (method_exists(System::getContainer(), 'getParameterBag'))
		{
			$objParameterBag = System::getContainer()->getParameterBag();

			foreach ($arrAttributes as $strAttrKey => $varAttrValue)
			{
				if (!\is_string($varAttrValue) || !preg_match('/%[a-z][a-z0-9_]*\.[a-z0-9_.]+%/i', $varAttrValue))
				{
					continue;
				}

				$varAttrValue = $objParameterBag->resolveValue($varAttrValue);
				$varAttrValue = $objParameterBag->unescapeValue($varAttrValue);

				$arrAttributes[$strAttrKey] = $varAttrValue;
			}
		}

		$arrAttributes['id'] = $strName;
		$arrAttributes['name'] = $strName;
		$arrAttributes['strField'] = $strField;
		$arrAttributes['strTable'] = $strTable;
		$arrAttributes['label'] = (($label = \is_array($arrData['label'] ?? null) ? $arrData['label'][0] : $arrData['label'] ?? null) !== null) ? $label : $strField;
		$arrAttributes['description'] = $arrData['label'][1] ?? null;
		$arrAttributes['type'] = $arrData['inputType'] ?? null;
		$arrAttributes['dataContainer'] = $objDca;
		$arrAttributes['value'] = StringUtil::deserialize($varValue);

		if ($arrData['eval']['basicEntities'] ?? null)
		{
			$arrAttributes['value'] = StringUtil::convertBasicEntities($arrAttributes['value']);
		}

		// Internet Explorer does not support onchange for checkboxes and radio buttons
		if ($arrData['eval']['submitOnChange'] ?? null)
		{
			if (($arrData['inputType'] ?? null) == 'checkbox' || ($arrData['inputType'] ?? null) == 'checkboxWizard' || ($arrData['inputType'] ?? null) == 'radio' || ($arrData['inputType'] ?? null) == 'radioTable')
			{
				$arrAttributes['onclick'] = trim(($arrAttributes['onclick'] ?? '') . " Backend.autoSubmit('" . $strTable . "')");
			}
			else
			{
				$arrAttributes['onchange'] = trim(($arrAttributes['onchange'] ?? '') . " Backend.autoSubmit('" . $strTable . "')");
			}
		}

		if (!empty($arrData['eval']['preserveTags']))
		{
			$arrAttributes['allowHtml'] = true;
		}

		if (!isset($arrAttributes['allowHtml']))
		{
			$rte = $arrData['eval']['rte'] ?? '';
			$arrAttributes['allowHtml'] = 'ace|html' === $rte || 0 === strpos($rte, 'tiny');
		}

		// Decode entities if HTML is allowed
		if ($arrAttributes['allowHtml'] || ($arrData['inputType'] ?? null) == 'fileTree')
		{
			$arrAttributes['decodeEntities'] = true;
		}

		// Add Ajax event
		if (($arrData['inputType'] ?? null) == 'checkbox' && ($arrData['eval']['submitOnChange'] ?? null) && \is_array($GLOBALS['TL_DCA'][$strTable]['subpalettes'] ?? null) && \array_key_exists($strField, $GLOBALS['TL_DCA'][$strTable]['subpalettes']))
		{
			$arrAttributes['onclick'] = "AjaxRequest.toggleSubpalette(this, 'sub_" . $strName . "', '" . $strField . "')";
		}

		// Options callback
		if (\is_array($arrData['options_callback'] ?? null))
		{
			$arrCallback = $arrData['options_callback'];
			$arrData['options'] = static::importStatic($arrCallback[0])->{$arrCallback[1]}($objDca);
		}
		elseif (\is_callable($arrData['options_callback'] ?? null))
		{
			$arrData['options'] = $arrData['options_callback']($objDca);
		}

		// Foreign key
		elseif (isset($arrData['foreignKey']))
		{
			$arrKey = explode('.', $arrData['foreignKey'], 2);
			$objOptions = Database::getInstance()->query("SELECT id, " . $arrKey[1] . " AS value FROM " . $arrKey[0] . " WHERE tstamp>0 ORDER BY value");
			$arrData['options'] = array();

			while ($objOptions->next())
			{
				$arrData['options'][$objOptions->id] = $objOptions->value;
			}
		}

		// Add default option to single checkbox
		if (($arrData['inputType'] ?? null) == 'checkbox' && !isset($arrData['options']) && !isset($arrData['options_callback']) && !isset($arrData['foreignKey']))
		{
			$request = System::getContainer()->get('request_stack')->getCurrentRequest();
			$isFrontend = $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request);

			if ($isFrontend && isset($arrAttributes['description']))
			{
				$arrAttributes['options'][] = array('value'=>1, 'label'=>$arrAttributes['description']);
			}
			else
			{
				$arrAttributes['options'][] = array('value'=>1, 'label'=>$arrAttributes['label']);
			}
		}

		// Add options
		if (\is_array($arrData['options'] ?? null))
		{
			$blnIsAssociative = ($arrData['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($arrData['options'] ?? null);
			$blnUseReference = isset($arrData['reference']);

			if (($arrData['eval']['includeBlankOption'] ?? null) && !($arrData['eval']['multiple'] ?? null))
			{
				$strLabel = $arrData['eval']['blankOptionLabel'] ?? '-';
				$arrAttributes['options'][] = array('value'=>'', 'label'=>$strLabel);
			}

			$unknown = (array) $arrAttributes['value'];

			foreach ($arrData['options'] as $k=>$v)
			{
				if (!\is_array($v))
				{
					$value = $blnIsAssociative ? $k : $v;

					if (($i = array_search($value, $unknown)) !== false)
					{
						unset($unknown[$i]);
					}

					$arrAttributes['options'][] = array('value'=>$value, 'label'=>($blnUseReference && isset($arrData['reference'][$v]) ? (($ref = (\is_array($arrData['reference'][$v]) ? $arrData['reference'][$v][0] : $arrData['reference'][$v])) ? $ref : $v) : $v));
					continue;
				}

				$key = $blnUseReference && isset($arrData['reference'][$k]) ? (($ref = (\is_array($arrData['reference'][$k]) ? $arrData['reference'][$k][0] : $arrData['reference'][$k])) ? $ref : $k) : $k;
				$blnIsAssoc = ArrayUtil::isAssoc($v);

				foreach ($v as $kk=>$vv)
				{
					$value = $blnIsAssoc ? $kk : $vv;

					if (($i = array_search($value, $unknown)) !== false)
					{
						unset($unknown[$i]);
					}

					$arrAttributes['options'][$key][] = array('value'=>$value, 'label'=>($blnUseReference && isset($arrData['reference'][$vv]) ? (($ref = (\is_array($arrData['reference'][$vv]) ? $arrData['reference'][$vv][0] : $arrData['reference'][$vv])) ? $ref : $vv) : $vv));
				}
			}

			$arrAttributes['unknownOption'] = array_filter($unknown);
		}

		if (\is_array($arrAttributes['sql'] ?? null) && !isset($arrAttributes['sql']['columnDefinition']))
		{
			if (!isset($arrAttributes['maxlength']) && isset($arrAttributes['sql']['length']))
			{
				$arrAttributes['maxlength'] = $arrAttributes['sql']['length'];
			}

			if (!isset($arrAttributes['unique']) && isset($arrAttributes['sql']['customSchemaOptions']['unique']))
			{
				$arrAttributes['unique'] = $arrAttributes['sql']['customSchemaOptions']['unique'];
			}
		}

		// Convert timestamps
		if ($varValue !== null && $varValue !== '' && \in_array($arrData['eval']['rgxp'] ?? null, array('date', 'time', 'datim')))
		{
			$objDate = new Date($varValue, Date::getFormatFromRgxp($arrData['eval']['rgxp']));
			$arrAttributes['value'] = $objDate->{$arrData['eval']['rgxp']};
		}

		// Convert URL insert tags
		if ($varValue && 'url' === ($arrData['eval']['rgxp'] ?? null))
		{
			$arrAttributes['value'] = str_replace('|urlattr}}', '}}', $varValue);
		}

		// Add the "rootNodes" array as attribute (see #3563)
		if (isset($arrData['rootNodes']) && !isset($arrData['eval']['rootNodes']))
		{
			$arrAttributes['rootNodes'] = $arrData['rootNodes'];
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getAttributesFromDca']) && \is_array($GLOBALS['TL_HOOKS']['getAttributesFromDca']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getAttributesFromDca'] as $callback)
			{
				$arrAttributes = static::importStatic($callback[0])->{$callback[1]}($arrAttributes, $objDca);
			}
		}

		return $arrAttributes;
	}

	/**
	 * Return the empty value based on the SQL string
	 *
	 * @return string|integer|null The empty value
	 */
	public function getEmptyValue()
	{
		if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['sql']))
		{
			return '';
		}

		return static::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['sql']);
	}

	/**
	 * Return the empty value based on the SQL string
	 *
	 * @param string|array $sql The SQL string
	 *
	 * @return string|integer|null The empty value
	 */
	public static function getEmptyValueByFieldType($sql)
	{
		if (empty($sql))
		{
			return '';
		}

		if (\is_array($sql))
		{
			if (isset($sql['columnDefinition']))
			{
				$sql = $sql['columnDefinition'];
			}
			else
			{
				if (isset($sql['notnull']) && !$sql['notnull'])
				{
					return null;
				}

				if (\in_array($sql['type'], array(Types::BIGINT, Types::DECIMAL, Types::INTEGER, Types::SMALLINT, Types::FLOAT)))
				{
					return 0;
				}

				if ($sql['type'] === Types::BOOLEAN)
				{
					return false;
				}

				return '';
			}
		}

		if (stripos($sql, 'NOT NULL') === false)
		{
			return null;
		}

		$type = strtolower(preg_replace('/^([A-Za-z]+)[( ].*$/', '$1', $sql));

		if (\in_array($type, array('int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'float', 'double', 'dec', 'decimal')))
		{
			return 0;
		}

		return '';
	}

	/**
	 * Return either an empty string or null based on the SQL string
	 *
	 * @return string|int|null The empty value
	 */
	public function getEmptyStringOrNull()
	{
		if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['sql']))
		{
			return '';
		}

		return static::getEmptyStringOrNullByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['sql']);
	}

	/**
	 * Return either an empty string or null based on the SQL string
	 *
	 * @param string $sql The SQL string
	 *
	 * @return string|null The empty string or null
	 */
	public static function getEmptyStringOrNullByFieldType($sql)
	{
		if (empty($sql))
		{
			return '';
		}

		return static::getEmptyValueByFieldType($sql) === null ? null : '';
	}

	protected static function specialcharsValue($strString): string
	{
		return str_replace(
			array('&amp;#35;', '&amp;#60;', '&amp;#62;', '&amp;#40;', '&amp;#41;', '&amp;#92;', '&amp;#61;', '&amp;#34;', '&amp;#39;'),
			array('&#35;', '&#60;', '&#62;', '&#40;', '&#41;', '&#92;', '&#61;', '&#34;', '&#39;'),
			StringUtil::specialchars((string) $strString, false, true),
		);
	}
}
