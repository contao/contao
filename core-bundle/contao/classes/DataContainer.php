<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\Image\ResizeConfiguration;
use Doctrine\DBAL\ArrayParameterType;

/**
 * Provide methods to handle data container arrays.
 *
 * @property string|integer $id
 * @property string         $table
 * @property mixed          $value
 * @property string         $field
 * @property string         $inputName
 * @property string         $palette
 * @property array          $rootIds
 * @property int            $currentPid
 */
abstract class DataContainer extends Backend
{
	/**
	 * Records are not sorted
	 */
	public const MODE_UNSORTED = 0;

	/**
	 * Records are sorted by a fixed field
	 */
	public const MODE_SORTED = 1;

	/**
	 * Records are sorted by a switchable field
	 */
	public const MODE_SORTABLE = 2;

	/**
	 * Records are sorted by the parent table
	 */
	public const MODE_SORTED_PARENT = 3;

	/**
	 * Displays the child records of a parent record (see content elements)
	 */
	public const MODE_PARENT = 4;

	/**
	 * Records are displayed as tree (see pages)
	 */
	public const MODE_TREE = 5;

	/**
	 * Displays the child records within a tree structure (see articles module)
	 */
	public const MODE_TREE_EXTENDED = 6;

	/**
	 * Sort by initial letter ascending
	 */
	public const SORT_INITIAL_LETTER_ASC = 1;

	/**
	 * Sort by initial letter descending
	 */
	public const SORT_INITIAL_LETTER_DESC = 2;

	/**
	 * Sort by initial two letters ascending
	 */
	public const SORT_INITIAL_LETTERS_ASC = 3;

	/**
	 * Sort by initial two letters descending
	 */
	public const SORT_INITIAL_LETTERS_DESC = 4;

	/**
	 * Sort by day ascending
	 */
	public const SORT_DAY_ASC = 5;

	/**
	 * Sort by day descending
	 */
	public const SORT_DAY_DESC = 6;

	/**
	 * Sort by month ascending
	 */
	public const SORT_MONTH_ASC = 7;

	/**
	 * Sort by month descending
	 */
	public const SORT_MONTH_DESC = 8;

	/**
	 * Sort by year ascending
	 */
	public const SORT_YEAR_ASC = 9;

	/**
	 * Sort by year descending
	 */
	public const SORT_YEAR_DESC = 10;

	/**
	 * Sort ascending
	 */
	public const SORT_ASC = 11;

	/**
	 * Sort descending
	 */
	public const SORT_DESC = 12;

	/**
	 * Sort by initial letter ascending and descending
	 */
	public const SORT_INITIAL_LETTER_BOTH = 13;

	/**
	 * Sort by initial two letters ascending and descending
	 */
	public const SORT_INITIAL_LETTERS_BOTH = 14;

	/**
	 * Sort by day ascending and descending
	 */
	public const SORT_DAY_BOTH = 15;

	/**
	 * Sort by month ascending and descending
	 */
	public const SORT_MONTH_BOTH = 16;

	/**
	 * Sort by year ascending and descending
	 */
	public const SORT_YEAR_BOTH = 17;

	/**
	 * Sort ascending and descending
	 */
	public const SORT_BOTH = 18;

	/**
	 * Current ID
	 * @var integer|string
	 */
	protected $intId;

	/**
	 * Name of the current table
	 * @var string
	 */
	protected $strTable;

	/**
	 * Name of the current field
	 * @var string
	 */
	protected $strField;

	/**
	 * Name attribute of the current input field
	 * @var string
	 */
	protected $strInputName;

	/**
	 * Value of the current field
	 * @var mixed
	 */
	protected $varValue;

	/**
	 * Name of the current palette
	 * @var string
	 */
	protected $strPalette;

	/**
	 * IDs of all root records (permissions)
	 * @var array
	 */
	protected $root = array();

	/**
	 * IDs of children of root records (permissions)
	 * @var array
	 */
	protected $rootChildren = array();

	/**
	 * IDs of visible parents of the root records
	 * @var array
	 */
	protected $visibleRootTrails = array();

	/**
	 * If pasting at root level is allowed (permissions)
	 * @var bool
	 */
	protected $rootPaste = false;

	/**
	 * WHERE clause of the database query
	 * @var array
	 */
	protected $procedure = array();

	/**
	 * Values for the WHERE clause of the database query
	 * @var array
	 */
	protected $values = array();

	/**
	 * Form attribute "onsubmit"
	 * @var array
	 */
	protected $onsubmit = array();

	/**
	 * Reload the page after the form has been submitted
	 * @var boolean
	 */
	protected $noReload = false;

	/**
	 * Active record
	 * @var Model|object|null
	 * @deprecated Deprecated since Contao 5.0, to be removed in Contao 6;
	 *             use DataContainer::getCurrentRecord() or DC_Table::getActiveRecord() instead.
	 */
	protected $objActiveRecord;

	/**
	 * True if one of the form fields is uploadable
	 * @var boolean
	 */
	protected $blnUploadable = false;

	/**
	 * DCA Picker instance
	 * @var PickerInterface
	 */
	protected $objPicker;

	/**
	 * Callback to convert DCA value to picker value
	 * @var callable
	 */
	protected $objPickerCallback;

	/**
	 * The picker value
	 * @var array
	 */
	protected $arrPickerValue = array();

	/**
	 * The picker field type
	 * @var string
	 */
	protected $strPickerFieldType;

	/**
	 * True if a new version has to be created
	 * @var boolean
	 */
	protected $blnCreateNewVersion = false;

	/**
	 * @var int
	 */
	protected $intCurrentPid;

	/**
	 * Current record cache
	 * @var array<int|string, array<string, mixed>|AccessDeniedException>
	 */
	private static $arrCurrentRecordCache = array();

	/**
	 * Current panel state
	 * @var bool
	 */
	protected $panelActive = false;

	/**
	 * Set an object property
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'activeRecord':
				trigger_deprecation('contao/core-bundle', '5.0', 'Setting the active record has been deprecated and will be removed in Contao 6.');
				$this->objActiveRecord = $varValue;
				break;

			case 'createNewVersion':
				$this->blnCreateNewVersion = (bool) $varValue;
				break;

			case 'id':
				$this->intId = $varValue;
				break;

			case 'field':
				$this->strField = $varValue;
				break;

			case 'inputName':
				$this->strInputName = $varValue;
				break;

			default:
				trigger_deprecation('contao/core-bundle', '5.0', 'Accessing protected properties or adding dynamic ones has been deprecated and will no longer work in Contao 6.');
				$this->$strKey = $varValue;
				break;
		}
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey
	 *
	 * @return mixed
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'id':
				return $this->intId;

			case 'table':
				return $this->strTable;

			case 'value':
				return $this->varValue;

			case 'field':
				return $this->strField;

			case 'inputName':
				return $this->strInputName;

			case 'palette':
				return $this->strPalette;

			case 'activeRecord':
				trigger_deprecation('contao/core-bundle', '5.0', 'The active record has been deprecated and will be removed in Contao 6. Use DataContainer::getCurrentRecord() or DC_Table::getActiveRecord() instead.');

				return $this->objActiveRecord;

			case 'createNewVersion':
				return $this->blnCreateNewVersion;

			case 'currentPid':
				return $this->intCurrentPid;
		}

		return parent::__get($strKey);
	}

	/**
	 * Render a row of a box and return it as HTML string
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 * @throws \Exception
	 */
	protected function row()
	{
		$arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField] ?? array();

		// Check if the field is excluded
		if (self::isFieldExcluded($this->strTable, $this->strField) && !System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $this->strField))
		{
			throw new AccessDeniedException('Field "' . $this->strTable . '.' . $this->strField . '" is excluded from being edited.');
		}

		$xlabel = '';

		// Add the help wizard
		if ($arrData['eval']['helpwizard'] ?? null)
		{
			$xlabel .= ' <a href="' . StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend_help', array('table' => $this->strTable, 'field' => $this->strField))) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['helpWizard']) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $arrData['label'][0] ?? '')) . '\',\'url\':this.href});return false">' . Image::getHtml('help.svg', $GLOBALS['TL_LANG']['MSC']['helpWizard']) . '</a>';
		}

		// Add a custom xlabel
		if (\is_array($arrData['xlabel'] ?? null))
		{
			foreach ($arrData['xlabel'] as $callback)
			{
				if (\is_array($callback))
				{
					$xlabel .= System::importStatic($callback[0])->{$callback[1]}($this);
				}
				elseif (\is_callable($callback))
				{
					$xlabel .= $callback($this);
				}
			}
		}

		// Input field callback
		if (\is_array($arrData['input_field_callback'] ?? null))
		{
			return System::importStatic($arrData['input_field_callback'][0])->{$arrData['input_field_callback'][1]}($this, $xlabel);
		}

		if (\is_callable($arrData['input_field_callback'] ?? null))
		{
			return $arrData['input_field_callback']($this, $xlabel);
		}

		/** @var class-string<Widget> $strClass */
		$strClass = $GLOBALS['BE_FFL'][$arrData['inputType'] ?? null] ?? null;

		// Return if the widget class does not exist
		if (!class_exists($strClass))
		{
			return '';
		}

		$arrData['eval']['required'] = false;

		if ($arrData['eval']['mandatory'] ?? null)
		{
			if (\is_array($this->varValue))
			{
				if (empty($this->varValue))
				{
					$arrData['eval']['required'] = true;
				}
			}
			elseif ('' === (string) $this->varValue)
			{
				$arrData['eval']['required'] = true;
			}
		}

		// Convert insert tags in src attributes (see #5965)
		if (isset($arrData['eval']['rte']) && str_starts_with($arrData['eval']['rte'], 'tiny') && \is_string($this->varValue))
		{
			$this->varValue = StringUtil::removeBasePath($this->varValue);
			$this->varValue = StringUtil::insertTagToSrc($this->varValue);
		}

		// Use raw request if set globally but allow opting out setting useRawRequestData to false explicitly
		$useRawGlobally = isset($GLOBALS['TL_DCA'][$this->strTable]['config']['useRawRequestData']) && $GLOBALS['TL_DCA'][$this->strTable]['config']['useRawRequestData'] === true;
		$notRawForField = isset($arrData['eval']['useRawRequestData']) && $arrData['eval']['useRawRequestData'] === false;

		if ($useRawGlobally && !$notRawForField)
		{
			$arrData['eval']['useRawRequestData'] = true;
		}

		$arrAttributes = $strClass::getAttributesFromDca($arrData, $this->strInputName, $this->varValue, $this->strField, $this->strTable, $this);

		$objWidget = new $strClass($arrAttributes);
		$objWidget->xlabel = $xlabel;
		$objWidget->currentRecord = $this->intId;

		// Validate and save the field
		if (Input::post('FORM_SUBMIT') == $this->strTable && $objWidget->submitInput() && Input::post($this->strInputName) !== null)
		{
			$objWidget->validate();

			if ($objWidget->hasErrors())
			{
				// Skip mandatory fields on auto-submit (see #4077)
				if (!$objWidget->mandatory || $objWidget->value || Input::post('SUBMIT_TYPE') != 'auto')
				{
					$this->noReload = true;
				}
			}
			// The return value of submitInput() might have changed, therefore check it again here (see #2383)
			elseif ($objWidget->submitInput())
			{
				$varValue = $objWidget->value;

				// Sort array by key (fix for JavaScript wizards)
				if (\is_array($varValue))
				{
					ksort($varValue);
					$varValue = serialize($varValue);
				}

				// Convert file paths in src attributes (see #5965)
				if ($varValue && isset($arrAttributes['rte']) && str_starts_with($arrAttributes['rte'], 'tiny'))
				{
					$varValue = StringUtil::srcToInsertTag($varValue);
					$varValue = StringUtil::addBasePath($varValue);
				}

				// Save the current value
				try
				{
					$this->save($varValue);

					// Confirm password changes
					if ($objWidget instanceof Password)
					{
						Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['pw_changed']);
					}
				}
				catch (ResponseException $e)
				{
					throw $e;
				}
				catch (\Exception $e)
				{
					$this->noReload = true;
					$objWidget->addError($e->getMessage());
				}
			}
		}

		$wizard = '';
		$strHelpClass = '';

		// Date picker
		if ($arrAttributes['datepicker'] ?? null)
		{
			$rgxp = $arrAttributes['rgxp'] ?? 'date';
			$format = Date::formatToJs(Config::get($rgxp . 'Format'));

			switch ($rgxp)
			{
				case 'datim':
					$time = ",\n        timePicker: true";
					break;

				case 'time':
					$time = ",\n        pickOnly: \"time\"";
					break;

				default:
					$time = '';
					break;
			}

			$strOnSelect = '';

			// Trigger the auto-submit function (see #8603)
			if ($arrAttributes['submitOnChange'] ?? null)
			{
				$strOnSelect = ",\n        onSelect: function() { Backend.autoSubmit(\"" . $this->strTable . "\"); }";
			}

			$wizard .= ' ' . Image::getHtml('assets/datepicker/images/icon.svg', '', 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['datepicker']) . '" id="toggle_' . $objWidget->id . '" style="cursor:pointer"') . '
  <script>
    window.addEvent("domready", function() {
      new Picker.Date($("ctrl_' . $objWidget->id . '"), {
        draggable: false,
        toggle: $("toggle_' . $objWidget->id . '"),
        format: "' . $format . '",
        positionOffset: {x:-211,y:-209}' . $time . ',
        pickerClass: "datepicker_bootstrap",
        useFadeInOut: !Browser.ie' . $strOnSelect . ',
        startDay: ' . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ',
        titleFormat: "' . $GLOBALS['TL_LANG']['MSC']['titleFormat'] . '"
      });
    });
  </script>';
		}

		// Color picker
		if ($arrAttributes['colorpicker'] ?? null)
		{
			// Support single fields as well (see #5240)
			$strKey = ($arrAttributes['multiple'] ?? null) ? $this->strField . '_0' : $this->strField;

			$wizard .= ' ' . Image::getHtml('pickcolor.svg', $GLOBALS['TL_LANG']['MSC']['colorpicker'], 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['colorpicker']) . '" id="moo_' . $this->strField . '" style="cursor:pointer"') . '
  <script>
    window.addEvent("domready", function() {
      var cl = $("ctrl_' . $strKey . '").value.hexToRgb(true) || [255, 0, 0];
      new MooRainbow("moo_' . $this->strField . '", {
        id: "ctrl_' . $strKey . '",
        startColor: cl,
        imgPath: "assets/colorpicker/images/",
        onComplete: function(color) {
          $("ctrl_' . $strKey . '").value = color.hex.replace("#", "");
        }
      });
    });
  </script>';
		}

		$arrClasses = StringUtil::trimsplit(' ', $arrAttributes['tl_class'] ?? '');

		// DCA picker
		if (isset($arrAttributes['dcaPicker']) && (\is_array($arrAttributes['dcaPicker']) || $arrAttributes['dcaPicker'] === true))
		{
			$arrClasses[] = 'dcapicker';
			$wizard .= Backend::getDcaPickerWizard($arrAttributes['dcaPicker'], $this->strTable, $this->strField, $this->strInputName);
		}

		if (($arrData['inputType'] ?? null) == 'password')
		{
			$wizard .= Backend::getTogglePasswordWizard($this->strInputName);
		}

		// Add a custom wizard
		if (\is_array($arrData['wizard'] ?? null))
		{
			foreach ($arrData['wizard'] as $callback)
			{
				if (\is_array($callback))
				{
					$wizard .= System::importStatic($callback[0])->{$callback[1]}($this);
				}
				elseif (\is_callable($callback))
				{
					$wizard .= $callback($this);
				}
			}
		}

		$hasWizardClass = \in_array('wizard', $arrClasses);

		if ($wizard && !($arrAttributes['disabled'] ?? false) && !($arrAttributes['readonly'] ?? false))
		{
			$objWidget->wizard = $wizard;

			if (!$hasWizardClass)
			{
				$arrClasses[] = 'wizard';
			}
		}
		elseif ($hasWizardClass)
		{
			unset($arrClasses[array_search('wizard', $arrClasses)]);
		}

		// Set correct form enctype
		if ($objWidget instanceof UploadableWidgetInterface)
		{
			$this->blnUploadable = true;
		}

		$arrClasses[] = 'widget';

		// Mark floated single checkboxes
		if (($arrData['inputType'] ?? null) == 'checkbox' && !($arrAttributes['multiple'] ?? null) && preg_grep('/^w\d+$/', $arrClasses))
		{
			$arrClasses[] = 'cbx';
		}
		elseif (($arrData['inputType'] ?? null) == 'text' && ($arrAttributes['multiple'] ?? null) && \in_array('wizard', $arrClasses))
		{
			$arrClasses[] = 'inline';
		}

		if (!empty($arrClasses))
		{
			$arrAttributes['tl_class'] = implode(' ', array_unique($arrClasses));
		}

		$updateMode = '';

		// Replace the textarea with an RTE instance
		if (!empty($arrAttributes['rte']))
		{
			list($file, $type) = explode('|', $arrAttributes['rte'], 2) + array(null, null);

			$fileBrowserTypes = array();
			$pickerBuilder = System::getContainer()->get('contao.picker.builder');

			foreach (array('file' => 'image', 'link' => 'file') as $context => $fileBrowserType)
			{
				if ($pickerBuilder->supportsContext($context))
				{
					$fileBrowserTypes[] = $fileBrowserType;
				}
			}

			$objTemplate = new BackendTemplate('be_' . $file);
			$objTemplate->selector = 'ctrl_' . $this->strInputName;
			$objTemplate->type = $type;
			$objTemplate->fileBrowserTypes = implode(' ', $fileBrowserTypes);
			$objTemplate->source = $this->strTable . '.' . $this->intId;
			$objTemplate->readonly = (bool) ($arrAttributes['readonly'] ?? false);

			$updateMode = $objTemplate->parse();

			unset($file, $type, $pickerBuilder, $fileBrowserTypes, $fileBrowserType);
		}

		// Handle multi-select fields in "override all" mode
		elseif ((($arrData['inputType'] ?? null) == 'checkbox' || ($arrData['inputType'] ?? null) == 'checkboxWizard') && ($arrAttributes['multiple'] ?? null) && Input::get('act') == 'overrideAll')
		{
			$updateMode = '
</div>
<div class="widget">
  <fieldset class="tl_radio_container">
  <legend>' . $GLOBALS['TL_LANG']['MSC']['updateMode'] . '</legend>
    <input type="radio" name="' . $this->strInputName . '_update" id="opt_' . $this->strInputName . '_update_1" class="tl_radio" value="add" data-action="focus->contao--scroll-offset#store"> <label for="opt_' . $this->strInputName . '_update_1">' . $GLOBALS['TL_LANG']['MSC']['updateAdd'] . '</label><br>
    <input type="radio" name="' . $this->strInputName . '_update" id="opt_' . $this->strInputName . '_update_2" class="tl_radio" value="remove" data-action="focus->contao--scroll-offset#store"> <label for="opt_' . $this->strInputName . '_update_2">' . $GLOBALS['TL_LANG']['MSC']['updateRemove'] . '</label><br>
    <input type="radio" name="' . $this->strInputName . '_update" id="opt_' . $this->strInputName . '_update_0" class="tl_radio" value="replace" checked="checked" data-action="focus->contao--scroll-offset#store"> <label for="opt_' . $this->strInputName . '_update_0">' . $GLOBALS['TL_LANG']['MSC']['updateReplace'] . '</label>
  </fieldset>';
		}

		$strPreview = '';

		// Show a preview image (see #4948)
		if ($this->strTable == 'tl_files' && $this->strField == 'name' && $this->objActiveRecord !== null && $this->objActiveRecord->type == 'file')
		{
			$objFile = new File($this->objActiveRecord->path);

			if ($objFile->isImage)
			{
				if (!$objFile->isSvgImage || ($objFile->viewWidth && $objFile->viewHeight))
				{
					$container = System::getContainer();
					$projectDir = $container->getParameter('kernel.project_dir');

					try
					{
						$image = rawurldecode($container->get('contao.image.factory')->create($projectDir . '/' . $objFile->path, array(699, 524, ResizeConfiguration::MODE_BOX))->getUrl($projectDir));
					}
					catch (\Exception $e)
					{
						Message::addError($e->getMessage());
						$image = Image::getPath('placeholder.svg');
					}
				}
				else
				{
					$image = Image::getPath('placeholder.svg');
				}

				$objImage = new File($image);
				$ctrl = 'ctrl_preview_' . substr(md5($image), 0, 8);

				$strPreview = '
<div id="' . $ctrl . '" class="tl_edit_preview">
  <img src="' . $objImage->dataUri . '" width="' . $objImage->width . '" height="' . $objImage->height . '" alt="">
</div>';

				// Add the script to mark the important part
				if (basename($image) !== 'placeholder.svg')
				{
					$strPreview .= '<script>Backend.editPreviewWizard($(\'' . $ctrl . '\'));</script>';

					if (Config::get('showHelp'))
					{
						$strPreview .= '<p class="tl_help tl_tip">' . $GLOBALS['TL_LANG'][$this->strTable]['edit_preview_help'] . '</p>';
					}

					$strPreview = '<div class="widget">' . $strPreview . '</div>';
				}
			}
		}

		return $strPreview . '
<div' . (!empty($arrAttributes['tl_class']) ? ' class="' . trim($arrAttributes['tl_class']) . '"' : '') . ($objWidget->hasErrors() ? ' data-contao--scroll-offset-target="widgetError"' : '') . '>' . $objWidget->parse() . $updateMode . (!$objWidget->hasErrors() ? $this->help($strHelpClass, $objWidget->description) : '') . '
</div>';
	}

	/**
	 * Return the field explanation as HTML string
	 *
	 * @param string $strClass
	 *
	 * @return string
	 */
	public function help($strClass='', $strDescription=null)
	{
		$return = $strDescription ?? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][1] ?? null;

		if (!$return || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] ?? null) == 'password' || !Config::get('showHelp'))
		{
			return '';
		}

		return '
  <p class="tl_help tl_tip' . $strClass . '">' . $return . '</p>';
	}

	/**
	 * Generate possible palette names from an array by taking the first value and either adding or not adding the following values
	 *
	 * @param array $names
	 *
	 * @return array
	 */
	protected function combiner($names)
	{
		$return = array('');
		$names = array_values($names);

		for ($i=0, $c=\count($names); $i<$c; $i++)
		{
			$buffer = array();

			foreach ($return as $k=>$v)
			{
				$buffer[] = ($k%2 == 0) ? $v : $v . $names[$i];
				$buffer[] = ($k%2 == 0) ? $v . $names[$i] : $v;
			}

			$return = $buffer;
		}

		return array_filter($return);
	}

	/**
	 * Return a query string that switches into edit mode
	 *
	 * @param integer $id
	 *
	 * @return string
	 */
	protected function switchToEdit($id)
	{
		$arrKeys = array();
		$arrUnset = array('act', 'key', 'id', 'table', 'mode', 'pid', 'data');

		foreach (Input::getKeys() as $strKey)
		{
			if (!\in_array($strKey, $arrUnset))
			{
				$arrKeys[$strKey] = $strKey . '=' . Input::get($strKey);
			}
		}

		$strUrl = System::getContainer()->get('router')->generate('contao_backend') . '?' . implode('&', $arrKeys);

		return $strUrl . (!empty($arrKeys) ? '&' : '') . (Input::get('table') ? 'table=' . Input::get('table') . '&amp;' : '') . 'act=edit&amp;id=' . rawurlencode($id);
	}

	/**
	 * @throws AccessDeniedException
	 */
	protected function denyAccessUnlessGranted($attribute, $subject): void
	{
		$security = System::getContainer()->get('security.helper');

		if ($security->isGranted($attribute, $subject))
		{
			return;
		}

		$message = match (true)
		{
			$subject instanceof ReadAction => \sprintf('Not enough permissions to read %s.', $subject),
			$subject instanceof CreateAction => \sprintf('Not enough permissions to create %s.', $subject),
			$subject instanceof UpdateAction => \sprintf('Not enough permissions to update %s.', $subject),
			$subject instanceof DeleteAction => \sprintf('Not enough permissions to delete %s.', $subject),
			default => 'Access denied.'
		};

		$exception = new AccessDeniedException($message);
		$exception->setAttributes($attribute);
		$exception->setSubject($subject);

		throw $exception;
	}

	/**
	 * Compile buttons from the table configuration array and return them as HTML
	 *
	 * @param array   $arrRow
	 * @param string  $strTable
	 * @param array   $arrRootIds
	 * @param boolean $blnCircularReference
	 * @param array   $arrChildRecordIds
	 * @param string  $strPrevious
	 * @param string  $strNext
	 *
	 * @return DataContainerOperationsBuilder
	 */
	protected function generateButtons($arrRow, $strTable, $arrRootIds=array(), $blnCircularReference=false, $arrChildRecordIds=null, $strPrevious=null, $strNext=null)
	{
		return System::getContainer()->get('contao.data_container.operations_builder')->initializeButtons(
			$strTable,
			$arrRow,
			$this,
			function (DataContainerOperation $config) use ($arrRow, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext) {
				trigger_deprecation('contao/core-bundle', '5.5', 'Using a button_callback without DataContainerOperation object is deprecated and will no longer work in Contao 6.');

				if (\is_array($config['button_callback'] ?? null))
				{
					$callback = System::importStatic($config['button_callback'][0]);
					$config->setHtml($callback->{$config['button_callback'][1]}($arrRow, $config['href'] ?? null, $config['label'], $config['title'], $config['icon'] ?? null, $config['attributes'], $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $this));
				}
				elseif (\is_callable($config['button_callback'] ?? null))
				{
					$config->setHtml($config['button_callback']($arrRow, $config['href'] ?? null, $config['label'], $config['title'], $config['icon'] ?? null, $config['attributes'], $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $this));
				}
			}
		);
	}

	/**
	 * Compile global buttons from the table configuration array and return them as HTML
	 *
	 * @return string
	 */
	protected function generateGlobalButtons()
	{
		if (!\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations'] ?? null))
		{
			return '';
		}

		$return = '';

		foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations'] as $k=>$v)
		{
			if (!($v['showOnSelect'] ?? null) && Input::get('act') == 'select')
			{
				continue;
			}

			$v = \is_array($v) ? $v : array($v);
			$title = $label = $k;

			if (isset($v['label']))
			{
				$label = \is_array($v['label']) ? $v['label'][0] : $v['label'];
				$title = \is_array($v['label']) ? ($v['label'][1] ?? null) : $v['label'];
			}

			$attributes = !empty($v['attributes']) ? ' ' . ltrim($v['attributes']) : '';

			// Custom icon (see #5541)
			if ($v['icon'] ?? null)
			{
				$v['class'] = trim(($v['class'] ?? '') . ' header_icon');

				// Add the theme path if only the file name is given
				if (!str_contains($v['icon'], '/'))
				{
					$v['icon'] = Image::getPath($v['icon']);
				}

				$attributes = \sprintf(' style="background-image:url(\'%s\')"', Controller::addAssetsUrlTo($v['icon'])) . $attributes;
			}

			if (!$label)
			{
				$label = $k;
			}

			if (!$title)
			{
				$title = $label;
			}

			// Call a custom function instead of using the default button
			if (\is_array($v['button_callback'] ?? null))
			{
				$return .= System::importStatic($v['button_callback'][0])->{$v['button_callback'][1]}($v['href'] ?? null, $label, $title, $v['class'] ?? null, $attributes, $this->strTable, $this->root);
				continue;
			}

			if (\is_callable($v['button_callback'] ?? null))
			{
				$return .= $v['button_callback']($v['href'] ?? null, $label, $title, $v['class'] ?? null, $attributes, $this->strTable, $this->root);
				continue;
			}

			if (!empty($v['route']))
			{
				$href = System::getContainer()->get('router')->generate($v['route']);
			}
			else
			{
				$href = $this->addToUrl($v['href'] ?? '');
			}

			$return .= '<a href="' . $href . '"' . (isset($v['class']) ? ' class="' . $v['class'] . '"' : '') . ' title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ';
		}

		return $return;
	}

	/**
	 * Compile header buttons from the table configuration array and return them as HTML
	 *
	 * @param array  $arrRow
	 * @param string $strPtable
	 *
	 * @return DataContainerOperationsBuilder
	 */
	protected function generateHeaderButtons($arrRow, $strPtable)
	{
		return System::getContainer()->get('contao.data_container.operations_builder')->initializeHeaderButtons(
			$strPtable,
			$arrRow,
			$this,
			function (DataContainerOperation $config) use ($arrRow, $strPtable) {
				trigger_deprecation('contao/core-bundle', '5.5', 'Using a button_callback without DataContainerOperation object is deprecated and will no longer work in Contao 6.');

				if (\is_array($config['button_callback'] ?? null))
				{
					$callback = System::importStatic($config['button_callback'][0]);
					$config->setHtml($callback->{$config['button_callback'][1]}($arrRow, $config['href'] ?? null, $config['label'], $config['title'], $config['icon'] ?? null, $config['attributes'], $strPtable, array(), null, false, null, null, $this));
				}
				elseif (\is_callable($config['button_callback'] ?? null))
				{
					$config->setHtml($config['button_callback']($arrRow, $config['href'] ?? null, $config['label'], $config['title'], $config['icon'] ?? null, $config['attributes'], $strPtable, array(), null, false, null, null, $this));
				}
			}
		);
	}

	/**
	 * Initialize the picker
	 *
	 * @param PickerInterface $picker
	 *
	 * @return array|null
	 */
	public function initPicker(PickerInterface $picker)
	{
		$provider = $picker->getCurrentProvider();

		if (!$provider instanceof DcaPickerProviderInterface || $provider->getDcaTable($picker->getConfig()) != $this->strTable)
		{
			return null;
		}

		$attributes = $provider->getDcaAttributes($picker->getConfig());

		if (isset($attributes['value']))
		{
			$this->arrPickerValue = (array) $attributes['value'];
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$arrClipboard = $objSession->get('CLIPBOARD');

		// Hide picker if the clipboard is not empty
		if (!empty($arrClipboard[$this->strTable]) || Input::get('act') == 'select')
		{
			return null;
		}

		$this->objPicker = $picker;
		$this->strPickerFieldType = $attributes['fieldType'];

		$this->objPickerCallback = static function ($value) use ($provider, $picker) {
			return $provider->convertDcaValue($picker->getConfig(), $value);
		};

		return $attributes;
	}

	/**
	 * Return the picker input field markup
	 *
	 * @param string $value
	 * @param string $attributes
	 *
	 * @return string
	 */
	protected function getPickerInputField($value, $attributes='')
	{
		$id = is_numeric($value) ? $value : md5($value);

		if (!\in_array($this->strPickerFieldType, array('checkbox', 'radio')))
		{
			return '';
		}

		$checked = Widget::optionChecked($value, $this->arrPickerValue);

		if ($checked)
		{
			$checked .= ' data-contao--scroll-offset-target="scrollTo"';
		}

		return \sprintf(
			' <input type="%s" name="picker%s" id="picker_%s" class="tl_tree_%s" value="%s" %s%s%s>',
			$this->strPickerFieldType,
			$this->strPickerFieldType === 'checkbox' ? '[]' : '',
			$id,
			$this->strPickerFieldType,
			StringUtil::specialchars(($this->objPickerCallback)($value)),
			'data-action="focus->contao--scroll-offset#store"',
			$checked,
			$attributes
		);
	}

	/**
	 * Return the data-picker-value attribute with the currently selected picker values (see #1816)
	 *
	 * @return string
	 */
	protected function getPickerValueAttribute()
	{
		// Only load the previously selected values for the checkbox field type (see #2346)
		if ($this->strPickerFieldType != 'checkbox')
		{
			return '';
		}

		$values = array_map($this->objPickerCallback, $this->arrPickerValue);
		$values = array_map('strval', $values);
		$values = json_encode($values);
		$values = htmlspecialchars($values, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

		return ' data-picker-value="' . $values . '"';
	}

	/**
	 * Build the sort panel and return it as string
	 *
	 * @return string
	 */
	protected function panel()
	{
		if (!($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] ?? null))
		{
			return '';
		}

		// Reset all filters
		if (Input::post('filter_reset') !== null && Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
			$data = $objSessionBag->all();

			unset(
				$data['filter'][$this->strTable],
				$data['filter'][$this->strTable . '_' . $this->currentPid],
				$data['sorting'][$this->strTable],
				$data['search'][$this->strTable]
			);

			$objSessionBag->replace($data);

			$this->reload();
		}

		$intFilterPanel = 0;
		$arrPanels = array();
		$arrPanes = StringUtil::trimsplit(';', $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] ?? '');

		foreach ($arrPanes as $strPanel)
		{
			$panels = '';
			$arrSubPanels = StringUtil::trimsplit(',', $strPanel);

			foreach ($arrSubPanels as $strSubPanel)
			{
				$panel = '';

				switch ($strSubPanel)
				{
					case 'limit':
						// The limit menu depends on other panels that may set a filter query, e.g. search and filter.
						// In order to correctly calculate the total row count, the limit menu must be compiled last.
						// We insert a placeholder here and compile the limit menu after all other panels.
						$panel = '###limit_menu###';
						break;

					case 'search':
						$panel = $this->searchMenu();
						break;

					case 'sort':
						$panel = $this->sortMenu();
						break;

					case 'filter':
						// Multiple filter sub-panels can be defined to split the fields across panels
						$panel = $this->filterMenu(++$intFilterPanel);
						break;

					default:
						// Call the panel_callback
						$arrCallback = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panel_callback'][$strSubPanel] ?? null;

						if (\is_array($arrCallback))
						{
							$panel = System::importStatic($arrCallback[0])->{$arrCallback[1]}($this);
						}
						elseif (\is_callable($arrCallback))
						{
							$panel = $arrCallback($this);
						}
				}

				// Add the panel if it is not empty
				if ($panel)
				{
					$panels .= $panel;
				}
			}

			// Add the group if it is not empty
			if ($panels)
			{
				$arrPanels[] = $panels;
			}
		}

		if (empty($arrPanels))
		{
			return '';
		}

		// Compile limit menu if placeholder is present
		foreach ($arrPanels as $key => $strPanel)
		{
			if (!str_contains($strPanel, '###limit_menu###'))
			{
				continue;
			}

			$arrPanels[$key] = str_replace('###limit_menu###', $this->limitMenu(), $strPanel);
		}

		if (Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			$this->reload();
		}

		$return = '';
		$intTotal = \count($arrPanels);
		$intLast = $intTotal - 1;

		for ($i=0; $i<$intTotal; $i++)
		{
			$submit = '';

			if ($i == $intLast)
			{
				$submit = '
<div class="tl_submit_panel tl_subpanel">
  <button name="filter" id="filter" class="tl_img_submit filter_apply" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['applyTitle']) . '">' . $GLOBALS['TL_LANG']['MSC']['apply'] . '</button>
  <button'. ($this->panelActive ? "" : " disabled") . ' name="filter_reset" id="filter_reset" value="1" class="tl_img_submit filter_reset" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['resetTitle']) . '">' . $GLOBALS['TL_LANG']['MSC']['reset'] . '</button>
</div>';
			}

			$return .= '
<div class="tl_panel">
  ' . $arrPanels[$i] . $submit. '
</div>';
		}

		$return = '
<form class="tl_form has-panels" method="post" aria-label="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['searchAndFilter']) . '">
<div class="tl_formbody">
  <input type="hidden" name="FORM_SUBMIT" value="tl_filters">
  <input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5) . '">
  ' . $return . '
</div>
</form>';

		return $return;
	}

	/**
	 * Invalidate the cache tags associated with a given DC
	 *
	 * Call this whenever an entry is modified (added, updated, deleted).
	 */
	public function invalidateCacheTags()
	{
		$tags = array('contao.db.' . $this->table . '.' . $this->id);

		$this->addPtableTags($this->table, $this->id, $tags);

		// Trigger the oninvalidate_cache_tags_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->table]['config']['oninvalidate_cache_tags_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->table]['config']['oninvalidate_cache_tags_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$tags = System::importStatic($callback[0])->{$callback[1]}($this, $tags);
				}
				elseif (\is_callable($callback))
				{
					$tags = $callback($this, $tags);
				}
			}
		}

		// Make sure tags are unique and empty ones are removed
		$tags = array_filter(array_unique($tags));

		System::getContainer()->get('contao.cache.tag_invalidator')->invalidateTags($tags);
	}

	public function addPtableTags($strTable, $intId, &$tags)
	{
		$ptable = $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['mode'] == 5 ? $strTable : ($GLOBALS['TL_DCA'][$strTable]['config']['ptable'] ?? null);

		if (!$ptable)
		{
			$tags[] = 'contao.db.' . $strTable;

			return;
		}

		Controller::loadDataContainer($ptable);

		$objPid = Database::getInstance()
			->prepare('SELECT pid FROM ' . Database::quoteIdentifier($strTable) . ' WHERE id=?')
			->execute($intId);

		if (!$objPid->numRows || $objPid->pid == 0)
		{
			$tags[] = 'contao.db.' . $strTable;

			return;
		}

		$tags[] = 'contao.db.' . $ptable . '.' . $objPid->pid;

		// Do not call recursively (see #4777)
	}

	/**
	 * Return the form field suffix
	 *
	 * @return integer|string
	 */
	protected function getFormFieldSuffix()
	{
		return $this->intId;
	}

	/**
	 * Return the name of the current palette
	 *
	 * @return string
	 */
	abstract public function getPalette();

	/**
	 * Save the current value
	 *
	 * @param mixed $varValue
	 *
	 * @throws \Exception
	 */
	abstract protected function save($varValue);

	/**
	 * Return the class name of the DataContainer driver for the given table.
	 *
	 * @param string $table
	 *
	 * @return string
	 */
	public static function getDriverForTable(string $table): string|null
	{
		return $GLOBALS['TL_DCA'][$table]['config']['dataContainer'] ?? null;
	}

	/**
	 * Return whether a DCA field is excluded.
	 *
	 * @param string $table
	 * @param string $field
	 *
	 * @return bool
	 */
	public static function isFieldExcluded(string $table, string $field): bool
	{
		if (DC_File::class === self::getDriverForTable($table))
		{
			return false;
		}

		if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['exclude']))
		{
			return (bool) $GLOBALS['TL_DCA'][$table]['fields'][$field]['exclude'];
		}

		return !empty($GLOBALS['TL_DCA'][$table]['fields'][$field]['inputType']) || !empty($GLOBALS['TL_DCA'][$table]['fields'][$field]['input_field_callback']);
	}

	/**
	 * Generates the label for a given data record according to the DCA configuration.
	 * Returns an array of strings if 'showColumns' is enabled in the DCA configuration.
	 *
	 * @param array  $row   The data record
	 * @param string $table The name of the data container
	 *
	 * @return string|array<string>
	 */
	public function generateRecordLabel(array $row, string|null $table = null, bool $protected = false, bool $isVisibleRootTrailPage = false)
	{
		$table = $table ?? $this->strTable;
		$labelConfig = &$GLOBALS['TL_DCA'][$table]['list']['label'];
		$args = array();

		foreach ($labelConfig['fields'] as $k=>$v)
		{
			if (str_contains($v, ':'))
			{
				list($strKey, $strTable) = explode(':', $v, 2);
				list($strTable, $strField) = explode('.', $strTable, 2);

				$objRef = Database::getInstance()
					->prepare("SELECT " . Database::quoteIdentifier($strField) . " FROM " . $strTable . " WHERE id=?")
					->limit(1)
					->execute($row[$strKey]);

				$args[$k] = $objRef->numRows ? $objRef->$strField : '';
			}
			elseif (isset($row[$v], $GLOBALS['TL_DCA'][$table]['fields'][$v]['foreignKey']))
			{
				$key = explode('.', $GLOBALS['TL_DCA'][$table]['fields'][$v]['foreignKey'], 2);

				$objRef = Database::getInstance()
					->prepare("SELECT " . Database::quoteIdentifier($key[1]) . " AS value FROM " . $key[0] . " WHERE id=?")
					->limit(1)
					->execute($row[$v]);

				$args[$k] = $objRef->numRows ? $objRef->value : '';
			}
			elseif (\in_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['flag'] ?? null, array(self::SORT_DAY_ASC, self::SORT_DAY_DESC, self::SORT_DAY_BOTH, self::SORT_MONTH_ASC, self::SORT_MONTH_DESC, self::SORT_MONTH_BOTH, self::SORT_YEAR_ASC, self::SORT_YEAR_DESC, self::SORT_YEAR_BOTH)))
			{
				if (($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['rgxp'] ?? null) == 'date')
				{
					$args[$k] = $row[$v] ? Date::parse(Config::get('dateFormat'), $row[$v]) : '-';
				}
				elseif (($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['rgxp'] ?? null) == 'time')
				{
					$args[$k] = $row[$v] ? Date::parse(Config::get('timeFormat'), $row[$v]) : '-';
				}
				else
				{
					$args[$k] = $row[$v] ? Date::parse(Config::get('datimFormat'), $row[$v]) : '-';
				}
			}
			elseif (($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['isBoolean'] ?? null) || (($GLOBALS['TL_DCA'][$table]['fields'][$v]['inputType'] ?? null) == 'checkbox' && !($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['multiple'] ?? null)))
			{
				$args[$k] = ($row[$v] ?? null) ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
			}
			elseif (isset($row[$v]))
			{
				$row_v = StringUtil::deserialize($row[$v]);

				if (\is_array($row_v))
				{
					$args_k = array();

					foreach ($row_v as $option)
					{
						$args_k[] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$option] ?? $option;
					}

					$args[$k] = implode(', ', iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($args_k)), false));
				}
				elseif (isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]]))
				{
					$args[$k] = \is_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]][0] : $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]];
				}
				elseif ((($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$table]['fields'][$v]['options'] ?? null)) && isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['options'][$row[$v]]))
				{
					$args[$k] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['options'][$row[$v]];
				}
				else
				{
					$args[$k] = $row[$v];
				}
			}
			else
			{
				$args[$k] = null;
			}
		}

		// Render the label
		$label = vsprintf($labelConfig['format'] ?? '%s', $args);

		// Shorten the label it if it is too long
		if (($labelConfig['maxCharacters'] ?? null) > 0 && $labelConfig['maxCharacters'] < \strlen(strip_tags($label)))
		{
			$label = trim(StringUtil::substrHtml($label, $labelConfig['maxCharacters'])) . ' …';
		}

		// Remove empty brackets (), [], {}, <> and empty tags from the label
		$label = preg_replace('/\( *\) ?|\[ *] ?|{ *} ?|< *> ?/', '', $label);
		$label = preg_replace('/<[^\/!][^>]+>\s*<\/[^>]+>/', '', $label);

		$mode = $GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? self::MODE_SORTED;

		// Execute label_callback
		if (\is_array($labelConfig['label_callback'] ?? null) || \is_callable($labelConfig['label_callback'] ?? null))
		{
			if (\in_array($mode, array(self::MODE_TREE, self::MODE_TREE_EXTENDED)))
			{
				if (\is_array($labelConfig['label_callback']))
				{
					$label = System::importStatic($labelConfig['label_callback'][0])->{$labelConfig['label_callback'][1]}($row, $label, $this, '', false, $protected, $isVisibleRootTrailPage);
				}
				elseif (\is_callable($labelConfig['label_callback']))
				{
					$label = $labelConfig['label_callback']($row, $label, $this, '', false, $protected, $isVisibleRootTrailPage);
				}
			}
			elseif ($mode === self::MODE_PARENT)
			{
				if (\is_array($labelConfig['label_callback']))
				{
					$label = System::importStatic($labelConfig['label_callback'][0])->{$labelConfig['label_callback'][1]}($row, $label, $this);
				}
				elseif (\is_callable($labelConfig['label_callback']))
				{
					$label = $labelConfig['label_callback']($row, $label, $this);
				}
			}
			else
			{
				if (\is_array($labelConfig['label_callback']))
				{
					$label = System::importStatic($labelConfig['label_callback'][0])->{$labelConfig['label_callback'][1]}($row, $label, $this, $args);
				}
				elseif (\is_callable($labelConfig['label_callback']))
				{
					$label = $labelConfig['label_callback']($row, $label, $this, $args);
				}
			}
		}
		elseif (\in_array($mode, array(self::MODE_TREE, self::MODE_TREE_EXTENDED)))
		{
			$label = Image::getHtml('iconPLAIN.svg') . ' ' . $label;
		}

		if (($labelConfig['showColumns'] ?? null) && !\in_array($mode, array(self::MODE_PARENT, self::MODE_TREE, self::MODE_TREE_EXTENDED)))
		{
			return \is_array($label) ? $label : $args;
		}

		return $label;
	}

	protected function markAsCopy(string $label, string $value): string
	{
		// Do not mark as copy more than once (see #6058)
		if (preg_match('/' . preg_quote(\sprintf($label, ''), '/') . '/', StringUtil::decodeEntities($value)))
		{
			return $value;
		}

		return \sprintf($label, $value);
	}

	/**
	 * @param array<int|string> $ids
	 */
	protected static function preloadCurrentRecords(array $ids, string $table): void
	{
		if (!\count($ids))
		{
			return;
		}

		// Clear current cache to make sure records are gone if they cannot be loaded from the database below
		foreach ($ids as $id)
		{
			self::clearCurrentRecordCache($id, $table);
		}

		$connection = System::getContainer()->get('database_connection');

		$stmt = $connection->executeQuery(
			'SELECT * FROM ' . $table . ' WHERE id IN (?)',
			array($ids),
			array(is_numeric(array_values($ids)[0]) ? ArrayParameterType::INTEGER : ArrayParameterType::STRING)
		);

		foreach ($stmt->iterateAssociative() as $row)
		{
			if (!\is_array($row))
			{
				continue;
			}

			static::setCurrentRecordCache($row['id'], $table, $row);
		}
	}

	/**
	 * @param array<string, mixed>|null $row Pass null to remove a given cache entry
	 */
	protected static function setCurrentRecordCache(int|string $id, string $table, array $row): void
	{
		self::$arrCurrentRecordCache[$table . '.' . $id] = $row;
	}

	/**
	 * Returns the database record. Does not contain any user changes at the moment you call it.
	 *
	 * @throws AccessDeniedException     If the current user has no read permission
	 * @return array<string, mixed>|null
	 */
	public function getCurrentRecord(int|string|null $id = null, string|null $table = null): array|null
	{
		$id = $id ?: $this->intId;
		$table = $table ?: $this->strTable;
		$key = $table . '.' . $id;

		if (!isset(self::$arrCurrentRecordCache[$key]))
		{
			static::preloadCurrentRecords(array($id), $table);
		}

		// In case this record was not part of the preloaded result, we don't need to apply any permission checks
		if (!isset(self::$arrCurrentRecordCache[$key]))
		{
			return null;
		}

		// In case this record has been checked before, we don't ask the voters again but instead throw the previous
		// exception
		if (self::$arrCurrentRecordCache[$key] instanceof AccessDeniedException)
		{
			throw self::$arrCurrentRecordCache[$key];
		}

		try
		{
			$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $table, new ReadAction($table, self::$arrCurrentRecordCache[$key]));
		}
		catch (AccessDeniedException $e)
		{
			// Remember the exception for this key for the next call
			self::$arrCurrentRecordCache[$key] = $e;

			throw $e;
		}

		return self::$arrCurrentRecordCache[$key];
	}

	public static function clearCurrentRecordCache(int|string|null $id = null, string|null $table = null): void
	{
		if (null === $table)
		{
			if (null !== $id)
			{
				throw new \InvalidArgumentException(\sprintf('Missing $table parameter for passed ID "%s".', $id));
			}

			self::$arrCurrentRecordCache = array();

			return;
		}

		if (null !== $id)
		{
			unset(self::$arrCurrentRecordCache["$table.$id"]);

			return;
		}

		foreach (array_keys(self::$arrCurrentRecordCache) as $key)
		{
			if (str_starts_with($key, "$table."))
			{
				unset(self::$arrCurrentRecordCache[$key]);
			}
		}
	}

	public function setPanelState(bool $state): void
	{
		if ($this->panelActive)
		{
			return;
		}

		$this->panelActive = $state;
	}
}
