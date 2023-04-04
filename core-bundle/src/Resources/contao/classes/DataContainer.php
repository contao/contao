<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Image\ResizeConfiguration;
use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Provide methods to handle data container arrays.
 *
 * @property string|integer $id
 * @property string         $table
 * @property mixed          $value
 * @property string         $field
 * @property string         $inputName
 * @property string         $palette
 * @property object|null    $activeRecord
 * @property array          $rootIds
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
	 * Records are displayed as tree (see site structure)
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
	 * @var Model|FilesModel
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
				$this->objActiveRecord = $varValue;
				break;

			case 'createNewVersion':
				$this->blnCreateNewVersion = (bool) $varValue;
				break;

			case 'id':
				$this->intId = $varValue;
				break;

			default:
				$this->$strKey = $varValue; // backwards compatibility
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
				return $this->objActiveRecord;

			case 'createNewVersion':
				return $this->blnCreateNewVersion;

			// Forward compatibility with Contao 5.0
			case 'currentPid':
				return ((int) (\defined('CURRENT_ID') ? CURRENT_ID : 0)) ?: null;
		}

		return parent::__get($strKey);
	}

	/**
	 * Render a row of a box and return it as HTML string
	 *
	 * @param string|array|null $strPalette
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 * @throws \Exception
	 */
	protected function row($strPalette=null)
	{
		$arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField] ?? array();

		// Check if the field is excluded
		if ($arrData['exclude'] ?? null)
		{
			throw new AccessDeniedException('Field "' . $this->strTable . '.' . $this->strField . '" is excluded from being edited.');
		}

		$xlabel = '';

		// Toggle line wrap (textarea)
		if (($arrData['inputType'] ?? null) == 'textarea' && !isset($arrData['eval']['rte']))
		{
			$xlabel .= ' ' . Image::getHtml('wrap.svg', $GLOBALS['TL_LANG']['MSC']['wordWrap'], 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['wordWrap']) . '" class="toggleWrap" onclick="Backend.toggleWrap(\'ctrl_' . $this->strInputName . '\')"');
		}

		// Add the help wizard
		if ($arrData['eval']['helpwizard'] ?? null)
		{
			$xlabel .= ' <a href="' . StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend_help', array('table' => $this->strTable, 'field' => $this->strField))) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['helpWizard']) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $arrData['label'][0] ?? '')) . '\',\'url\':this.href});return false">' . Image::getHtml('about.svg', $GLOBALS['TL_LANG']['MSC']['helpWizard']) . '</a>';
		}

		// Add a custom xlabel
		if (\is_array($arrData['xlabel'] ?? null))
		{
			foreach ($arrData['xlabel'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$xlabel .= $this->{$callback[0]}->{$callback[1]}($this);
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
			$this->import($arrData['input_field_callback'][0]);

			return $this->{$arrData['input_field_callback'][0]}->{$arrData['input_field_callback'][1]}($this, $xlabel);
		}

		if (\is_callable($arrData['input_field_callback'] ?? null))
		{
			return $arrData['input_field_callback']($this, $xlabel);
		}

		$strClass = $GLOBALS['BE_FFL'][($arrData['inputType'] ?? null)] ?? null;

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
		if (isset($arrData['eval']['rte']) && strncmp($arrData['eval']['rte'], 'tiny', 4) === 0 && \is_string($this->varValue))
		{
			$this->varValue = StringUtil::insertTagToSrc($this->varValue);
		}

		// Use raw request if set globally but allow opting out setting useRawRequestData to false explicitly
		$useRawGlobally = isset($GLOBALS['TL_DCA'][$this->strTable]['config']['useRawRequestData']) && $GLOBALS['TL_DCA'][$this->strTable]['config']['useRawRequestData'] === true;
		$notRawForField = isset($arrData['eval']['useRawRequestData']) && $arrData['eval']['useRawRequestData'] === false;

		if ($useRawGlobally && !$notRawForField)
		{
			$arrData['eval']['useRawRequestData'] = true;
		}

		/** @var Widget $objWidget */
		$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $this->strInputName, $this->varValue, $this->strField, $this->strTable, $this));
		$objWidget->xlabel = $xlabel;
		$objWidget->currentRecord = $this->intId;

		// Validate the field
		if (Input::post('FORM_SUBMIT') == $this->strTable)
		{
			$suffix = $this->getFormFieldSuffix();
			$key = (Input::get('act') == 'editAll') ? 'FORM_FIELDS_' . $suffix : 'FORM_FIELDS';

			// Calculate the current palette
			$postPaletteFields = implode(',', Input::post($key));
			$postPaletteFields = array_unique(StringUtil::trimsplit('[,;]', $postPaletteFields));

			// Compile the palette if there is none
			if ($strPalette === null)
			{
				$newPaletteFields = StringUtil::trimsplit('[,;]', $this->getPalette());
			}
			else
			{
				// Use the given palette ($strPalette is an array in editAll mode)
				$newPaletteFields = \is_array($strPalette) ? $strPalette : StringUtil::trimsplit('[,;]', $strPalette);

				// Recompile the palette if the current field is a selector field and the value has changed
				if (isset($GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__']) && $this->varValue != Input::post($this->strInputName) && \in_array($this->strField, $GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__']))
				{
					$newPaletteFields = StringUtil::trimsplit('[,;]', $this->getPalette());
				}
			}

			// Adjust the names in editAll mode
			if (Input::get('act') == 'editAll')
			{
				foreach ($newPaletteFields as $k=>$v)
				{
					$newPaletteFields[$k] = $v . '_' . $suffix;
				}
			}

			$paletteFields = array_intersect($postPaletteFields, $newPaletteFields);

			// Deprecated since Contao 4.2, to be removed in Contao 5.0
			if (!isset($_POST[$this->strInputName]) && \in_array($this->strInputName, $paletteFields))
			{
				trigger_deprecation('contao/core-bundle', '4.2', 'Using $_POST[\'FORM_FIELDS\'] has been deprecated and will no longer work in Contao 5.0. Make sure to always submit at least an empty string in your widget.');
			}

			// Validate and save the field
			if ($objWidget->submitInput() && (\in_array($this->strInputName, $paletteFields) || Input::get('act') == 'overrideAll'))
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
					if ($varValue && isset($arrData['eval']['rte']) && strncmp($arrData['eval']['rte'], 'tiny', 4) === 0)
					{
						$varValue = StringUtil::srcToInsertTag($varValue);
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
		}

		$wizard = '';
		$strHelpClass = '';

		// Date picker
		if ($arrData['eval']['datepicker'] ?? null)
		{
			$rgxp = $arrData['eval']['rgxp'] ?? 'date';
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
			if ($arrData['eval']['submitOnChange'] ?? null)
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
		if ($arrData['eval']['colorpicker'] ?? null)
		{
			// Support single fields as well (see #5240)
			$strKey = ($arrData['eval']['multiple'] ?? null) ? $this->strField . '_0' : $this->strField;

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

		$arrClasses = StringUtil::trimsplit(' ', $arrData['eval']['tl_class'] ?? '');

		// DCA picker
		if (isset($arrData['eval']['dcaPicker']) && (\is_array($arrData['eval']['dcaPicker']) || $arrData['eval']['dcaPicker'] === true))
		{
			$arrClasses[] = 'dcapicker';
			$wizard .= Backend::getDcaPickerWizard($arrData['eval']['dcaPicker'], $this->strTable, $this->strField, $this->strInputName);
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
					$this->import($callback[0]);
					$wizard .= $this->{$callback[0]}->{$callback[1]}($this);
				}
				elseif (\is_callable($callback))
				{
					$wizard .= $callback($this);
				}
			}
		}

		$hasWizardClass = \in_array('wizard', $arrClasses);

		if ($wizard && !($arrData['eval']['disabled'] ?? false) && !($arrData['eval']['readonly'] ?? false))
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
		if (($arrData['inputType'] ?? null) == 'checkbox' && !($arrData['eval']['multiple'] ?? null) && \in_array('w50', $arrClasses))
		{
			$arrClasses[] = 'cbx';
		}
		elseif (($arrData['inputType'] ?? null) == 'text' && ($arrData['eval']['multiple'] ?? null) && \in_array('wizard', $arrClasses))
		{
			$arrClasses[] = 'inline';
		}

		if (!empty($arrClasses))
		{
			$arrData['eval']['tl_class'] = implode(' ', array_unique($arrClasses));
		}

		$updateMode = '';

		// Replace the textarea with an RTE instance
		if (!empty($arrData['eval']['rte']))
		{
			list($file, $type) = explode('|', $arrData['eval']['rte'], 2) + array(null, null);

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
			$objTemplate->fileBrowserTypes = $fileBrowserTypes;
			$objTemplate->source = $this->strTable . '.' . $this->intId;

			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			$objTemplate->language = Backend::getTinyMceLanguage();

			$updateMode = $objTemplate->parse();

			unset($file, $type, $pickerBuilder, $fileBrowserTypes, $fileBrowserType);
		}

		// Handle multi-select fields in "override all" mode
		elseif ((($arrData['inputType'] ?? null) == 'checkbox' || ($arrData['inputType'] ?? null) == 'checkboxWizard') && ($arrData['eval']['multiple'] ?? null) && Input::get('act') == 'overrideAll')
		{
			$updateMode = '
</div>
<div class="widget">
  <fieldset class="tl_radio_container">
  <legend>' . $GLOBALS['TL_LANG']['MSC']['updateMode'] . '</legend>
    <input type="radio" name="' . $this->strInputName . '_update" id="opt_' . $this->strInputName . '_update_1" class="tl_radio" value="add" onfocus="Backend.getScrollOffset()"> <label for="opt_' . $this->strInputName . '_update_1">' . $GLOBALS['TL_LANG']['MSC']['updateAdd'] . '</label><br>
    <input type="radio" name="' . $this->strInputName . '_update" id="opt_' . $this->strInputName . '_update_2" class="tl_radio" value="remove" onfocus="Backend.getScrollOffset()"> <label for="opt_' . $this->strInputName . '_update_2">' . $GLOBALS['TL_LANG']['MSC']['updateRemove'] . '</label><br>
    <input type="radio" name="' . $this->strInputName . '_update" id="opt_' . $this->strInputName . '_update_0" class="tl_radio" value="replace" checked="checked" onfocus="Backend.getScrollOffset()"> <label for="opt_' . $this->strInputName . '_update_0">' . $GLOBALS['TL_LANG']['MSC']['updateReplace'] . '</label>
  </fieldset>';
		}

		$strPreview = '';

		// Show a preview image (see #4948)
		if ($this->strTable == 'tl_files' && $this->strField == 'name' && $this->objActiveRecord !== null && $this->objActiveRecord->type == 'file')
		{
			$objFile = new File($this->objActiveRecord->path);

			if ($objFile->isImage)
			{
				$blnCanResize = true;

				if ($objFile->isSvgImage)
				{
					// SVG images with undefined sizes cannot be resized
					if (!$objFile->viewWidth || !$objFile->viewHeight)
					{
						$blnCanResize= false;
					}
				}
				elseif (System::getContainer()->get('contao.image.imagine') instanceof Imagine)
				{
					// Check the maximum width and height if the GDlib is used to resize images
					if ($objFile->height > Config::get('gdMaxImgHeight') || $objFile->width > Config::get('gdMaxImgWidth'))
					{
						$blnCanResize = false;
					}
				}

				if ($blnCanResize)
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
<div' . (!empty($arrData['eval']['tl_class']) ? ' class="' . trim($arrData['eval']['tl_class']) . '"' : '') . '>' . $objWidget->parse() . $updateMode . (!$objWidget->hasErrors() ? $this->help($strHelpClass) : '') . '
</div>';
	}

	/**
	 * Return the field explanation as HTML string
	 *
	 * @param string $strClass
	 *
	 * @return string
	 */
	public function help($strClass='')
	{
		$return = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][1] ?? null;

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
		$arrUnset = array('act', 'key', 'id', 'table', 'mode', 'pid');

		foreach (array_keys($_GET) as $strKey)
		{
			if (!\in_array($strKey, $arrUnset))
			{
				$arrKeys[$strKey] = $strKey . '=' . Input::get($strKey);
			}
		}

		$strUrl = TL_SCRIPT . '?' . implode('&', $arrKeys);

		return $strUrl . (!empty($arrKeys) ? '&' : '') . (Input::get('table') ? 'table=' . Input::get('table') . '&amp;' : '') . 'act=edit&amp;id=' . rawurlencode($id);
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
	 * @return string
	 */
	protected function generateButtons($arrRow, $strTable, $arrRootIds=array(), $blnCircularReference=false, $arrChildRecordIds=null, $strPrevious=null, $strNext=null)
	{
		if (!\is_array($GLOBALS['TL_DCA'][$strTable]['list']['operations'] ?? null))
		{
			return '';
		}

		$return = '';

		foreach ($GLOBALS['TL_DCA'][$strTable]['list']['operations'] as $k=>$v)
		{
			$v = \is_array($v) ? $v : array($v);
			$id = StringUtil::specialchars(rawurldecode($arrRow['id']));
			$label = $title = $k;

			if (isset($v['label']))
			{
				if (\is_array($v['label']))
				{
					$label = $v['label'][0] ?? null;
					$title = sprintf($v['label'][1] ?? '', $id);
				}
				else
				{
					$label = $title = sprintf($v['label'], $id);
				}
			}

			$attributes = !empty($v['attributes']) ? ' ' . ltrim(sprintf($v['attributes'], $id, $id)) : '';

			// Add the key as CSS class
			if (strpos($attributes, 'class="') !== false)
			{
				$attributes = str_replace('class="', 'class="' . $k . ' ', $attributes);
			}
			else
			{
				$attributes = ' class="' . $k . '"' . $attributes;
			}

			// Call a custom function instead of using the default button
			if (\is_array($v['button_callback'] ?? null))
			{
				$this->import($v['button_callback'][0]);
				$return .= $this->{$v['button_callback'][0]}->{$v['button_callback'][1]}($arrRow, $v['href'] ?? null, $label, $title, $v['icon'] ?? null, $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $this);
				continue;
			}

			if (\is_callable($v['button_callback'] ?? null))
			{
				$return .= $v['button_callback']($arrRow, $v['href'] ?? null, $label, $title, $v['icon'] ?? null, $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $this);
				continue;
			}

			// Generate all buttons except "move up" and "move down" buttons
			if ($k != 'move' && $v != 'move')
			{
				if ($k == 'show')
				{
					if (!empty($v['route']))
					{
						$href = System::getContainer()->get('router')->generate($v['route'], array('id' => $arrRow['id'], 'popup' => '1'));
					}
					else
					{
						$href = $this->addToUrl(($v['href'] ?? '') . '&amp;id=' . $arrRow['id'] . '&amp;popup=1');
					}

					$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $label)) . '\',\'url\':this.href});return false"' . $attributes . '>' . Image::getHtml($v['icon'], $label) . '</a> ';
				}
				else
				{
					if (!empty($v['route']))
					{
						$href = System::getContainer()->get('router')->generate($v['route'], array('id' => $arrRow['id']));
					}
					else
					{
						$href = $this->addToUrl(($v['href'] ?? '') . '&amp;id=' . $arrRow['id'] . (Input::get('nb') ? '&amp;nc=1' : ''));
					}

					parse_str(StringUtil::decodeEntities($v['href'] ?? ''), $params);

					if (($params['act'] ?? null) == 'toggle' && isset($params['field']))
					{
						// Hide the toggle icon if the user does not have access to the field
						if (($GLOBALS['TL_DCA'][$strTable]['fields'][$params['field']]['toggle'] ?? false) !== true || (($GLOBALS['TL_DCA'][$strTable]['fields'][$params['field']]['exclude'] ?? false) && !System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $strTable . '::' . $params['field'])))
						{
							continue;
						}

						$icon = $v['icon'];
						$_icon = pathinfo($v['icon'], PATHINFO_FILENAME) . '_.' . pathinfo($v['icon'], PATHINFO_EXTENSION);

						if (false !== strpos($v['icon'], '/'))
						{
							$_icon = \dirname($v['icon']) . '/' . $_icon;
						}

						if ($icon == 'visible.svg')
						{
							$_icon = 'invisible.svg';
						}

						$state = $arrRow[$params['field']] ? 1 : 0;

						if ($v['reverse'] ?? false)
						{
							$state = $arrRow[$params['field']] ? 0 : 1;
						}

						$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,' . ($icon == 'visible.svg' ? 'true' : 'false') . ')">' . Image::getHtml($state ? $icon : $_icon, $label, 'data-icon="' . Image::getPath($icon) . '" data-icon-disabled="' . Image::getPath($_icon) . '" data-state="' . $state . '"') . '</a> ';
					}
					else
					{
						$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($v['icon'], $label) . '</a> ';
					}
				}

				continue;
			}

			trigger_deprecation('contao/core-bundle', '4.13', 'The DCA "move" operation is deprecated and will be removed in Contao 5.');

			$arrDirections = array('up', 'down');
			$arrRootIds = \is_array($arrRootIds) ? $arrRootIds : array($arrRootIds);

			foreach ($arrDirections as $dir)
			{
				$label = !empty($GLOBALS['TL_LANG'][$strTable][$dir][0]) ? $GLOBALS['TL_LANG'][$strTable][$dir][0] : $dir;
				$title = !empty($GLOBALS['TL_LANG'][$strTable][$dir][1]) ? $GLOBALS['TL_LANG'][$strTable][$dir][1] : $dir;

				$label = Image::getHtml($dir . '.svg', $label);
				$href = !empty($v['href']) ? $v['href'] : '&amp;act=move';

				if ($dir == 'up')
				{
					$return .= ((is_numeric($strPrevious) && (empty($GLOBALS['TL_DCA'][$strTable]['list']['sorting']['root']) || !\in_array($arrRow['id'], $arrRootIds))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $arrRow['id']) . '&amp;sid=' . (int) $strPrevious . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ' : Image::getHtml('up_.svg')) . ' ';
				}
				else
				{
					$return .= ((is_numeric($strNext) && (empty($GLOBALS['TL_DCA'][$strTable]['list']['sorting']['root']) || !\in_array($arrRow['id'], $arrRootIds))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $arrRow['id']) . '&amp;sid=' . (int) $strNext . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ' : Image::getHtml('down_.svg')) . ' ';
				}
			}
		}

		return trim($return);
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
				if (strpos($v['icon'], '/') === false)
				{
					$v['icon'] = Image::getPath($v['icon']);
				}

				$attributes = sprintf(' style="background-image:url(\'%s\')"', Controller::addAssetsUrlTo($v['icon'])) . $attributes;
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
				$this->import($v['button_callback'][0]);
				$return .= $this->{$v['button_callback'][0]}->{$v['button_callback'][1]}($v['href'] ?? null, $label, $title, $v['class'] ?? null, $attributes, $this->strTable, $this->root);
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
	 * @return string
	 */
	protected function generateHeaderButtons($arrRow, $strPtable)
	{
		if (!\is_array($GLOBALS['TL_DCA'][$strPtable]['list']['operations'] ?? null))
		{
			return '';
		}

		$return = '';

		foreach ($GLOBALS['TL_DCA'][$strPtable]['list']['operations'] as $k=> $v)
		{
			if (empty($v['showInHeader']) || (Input::get('act') == 'select' && !($v['showOnSelect'] ?? null)))
			{
				continue;
			}

			$v = \is_array($v) ? $v : array($v);
			$id = StringUtil::specialchars(rawurldecode($arrRow['id']));
			$label = $title = $k;

			if (isset($v['label']))
			{
				if (\is_array($v['label']))
				{
					$label = $v['label'][0];
					$title = sprintf($v['label'][1], $id);
				}
				else
				{
					$label = $title = sprintf($v['label'], $id);
				}
			}

			$attributes = !empty($v['attributes']) ? ' ' . ltrim(sprintf($v['attributes'], $id, $id)) : '';

			// Add the key as CSS class
			if (strpos($attributes, 'class="') !== false)
			{
				$attributes = str_replace('class="', 'class="' . $k . ' ', $attributes);
			}
			else
			{
				$attributes = ' class="' . $k . '"' . $attributes;
			}

			// Add the parent table to the href
			if (isset($v['href']))
			{
				$v['href'] .= '&amp;table=' . $strPtable;
			}
			else
			{
				$v['href'] = 'table=' . $strPtable;
			}

			// Call a custom function instead of using the default button
			if (\is_array($v['button_callback'] ?? null))
			{
				$this->import($v['button_callback'][0]);
				$return .= $this->{$v['button_callback'][0]}->{$v['button_callback'][1]}($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strPtable, array(), null, false, null, null, $this);
				continue;
			}

			if (\is_callable($v['button_callback'] ?? null))
			{
				$return .= $v['button_callback']($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strPtable, array(), null, false, null, null, $this);
				continue;
			}

			if ($k == 'show')
			{
				if (!empty($v['route']))
				{
					$href = System::getContainer()->get('router')->generate($v['route'], array('id' => $arrRow['id'], 'popup' => '1'));
				}
				else
				{
					$href = $this->addToUrl($v['href'] . '&amp;id=' . $arrRow['id'] . '&amp;popup=1');
				}

				$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", sprintf(\is_array($GLOBALS['TL_LANG'][$strPtable]['show'] ?? null) ? $GLOBALS['TL_LANG'][$strPtable]['show'][1] : ($GLOBALS['TL_LANG'][$strPtable]['show'] ?? ''), $arrRow['id']))) . '\',\'url\':this.href});return false"' . $attributes . '>' . Image::getHtml($v['icon'], $label) . '</a> ';
			}
			else
			{
				if (!empty($v['route']))
				{
					$href = System::getContainer()->get('router')->generate($v['route'], array('id' => $arrRow['id']));
				}
				else
				{
					$href = $this->addToUrl($v['href'] . '&amp;id=' . $arrRow['id'] . (Input::get('nb') ? '&amp;nc=1' : ''));
				}

				parse_str(StringUtil::decodeEntities($v['href']), $params);

				if (($params['act'] ?? null) == 'toggle' && isset($params['field']))
				{
					// Hide the toggle icon if the user does not have access to the field
					if (($GLOBALS['TL_DCA'][$strPtable]['fields'][$params['field']]['toggle'] ?? false) !== true || (($GLOBALS['TL_DCA'][$strPtable]['fields'][$params['field']]['exclude'] ?? false) && !System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $strPtable . '::' . $params['field'])))
					{
						continue;
					}

					$icon = $v['icon'];
					$_icon = pathinfo($v['icon'], PATHINFO_FILENAME) . '_.' . pathinfo($v['icon'], PATHINFO_EXTENSION);

					if (false !== strpos($v['icon'], '/'))
					{
						$_icon = \dirname($v['icon']) . '/' . $_icon;
					}

					if ($icon == 'visible.svg')
					{
						$_icon = 'invisible.svg';
					}

					$state = $arrRow[$params['field']] ? 1 : 0;

					if ($v['reverse'] ?? false)
					{
						$state = $arrRow[$params['field']] ? 0 : 1;
					}

					$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this)">' . Image::getHtml($state ? $icon : $_icon, $label, 'data-icon="' . Image::getPath($icon) . '" data-icon-disabled="' . Image::getPath($_icon) . '" data-state="' . $state . '"') . '</a> ';
				}
				else
				{
					$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($v['icon'], $label) . '</a> ';
				}
			}
		}

		return $return;
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

		$this->objPicker = $picker;
		$this->strPickerFieldType = $attributes['fieldType'];

		$this->objPickerCallback = static function ($value) use ($picker, $provider)
		{
			return $provider->convertDcaValue($picker->getConfig(), $value);
		};

		if (isset($attributes['value']))
		{
			$this->arrPickerValue = (array) $attributes['value'];
		}

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

		switch ($this->strPickerFieldType)
		{
			case 'checkbox':
				return ' <input type="checkbox" name="picker[]" id="picker_' . $id . '" class="tl_tree_checkbox" value="' . StringUtil::specialchars(($this->objPickerCallback)($value)) . '" onfocus="Backend.getScrollOffset()"' . Widget::optionChecked($value, $this->arrPickerValue) . $attributes . '>';

			case 'radio':
				return ' <input type="radio" name="picker" id="picker_' . $id . '" class="tl_tree_radio" value="' . StringUtil::specialchars(($this->objPickerCallback)($value)) . '" onfocus="Backend.getScrollOffset()"' . Widget::optionChecked($value, $this->arrPickerValue) . $attributes . '>';
		}

		return '';
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
		$values = htmlspecialchars($values);

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
		if (isset($_POST['filter_reset']) && Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			/** @var AttributeBagInterface $objSessionBag */
			$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');
			$data = $objSessionBag->all();

			unset(
				$data['filter'][$this->strTable],
				$data['filter'][$this->strTable . '_' . CURRENT_ID],
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
						// Multiple filter subpanels can be defined to split the fields across panels
						$panel = $this->filterMenu(++$intFilterPanel);
						break;

					default:
						// Call the panel_callback
						$arrCallback = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panel_callback'][$strSubPanel] ?? null;

						if (\is_array($arrCallback))
						{
							$this->import($arrCallback[0]);
							$panel = $this->{$arrCallback[0]}->{$arrCallback[1]}($this);
						}
						elseif (\is_callable($arrCallback))
						{
							$panel = $arrCallback($this);
						}
				}

				// Add the panel if it is not empty
				if ($panel)
				{
					$panels = $panel . $panels;
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
			if (strpos($strPanel, '###limit_menu###') === false)
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
  <button name="filter_reset" id="filter_reset" value="1" class="tl_img_submit filter_reset" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['resetTitle']) . '">' . $GLOBALS['TL_LANG']['MSC']['reset'] . '</button>
</div>';
			}

			$return .= '
<div class="tl_panel cf">
  ' . $submit . $arrPanels[$i] . '
</div>';
		}

		$return = '
<form class="tl_form" method="post" aria-label="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['searchAndFilter']) . '">
<div class="tl_formbody">
  <input type="hidden" name="FORM_SUBMIT" value="tl_filters">
  <input type="hidden" name="REQUEST_TOKEN" value="' . REQUEST_TOKEN . '">
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
		if (!System::getContainer()->has('fos_http_cache.cache_manager'))
		{
			return;
		}

		$tags = array('contao.db.' . $this->table . '.' . $this->id);

		$this->addPtableTags($this->table, $this->id, $tags);

		// Trigger the oninvalidate_cache_tags_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->table]['config']['oninvalidate_cache_tags_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->table]['config']['oninvalidate_cache_tags_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$tags = $this->{$callback[0]}->{$callback[1]}($this, $tags);
				}
				elseif (\is_callable($callback))
				{
					$tags = $callback($this, $tags);
				}
			}
		}

		// Make sure tags are unique and empty ones are removed
		$tags = array_filter(array_unique($tags));

		System::getContainer()->get('fos_http_cache.cache_manager')->invalidateTags($tags);
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

		$objPid = $this->Database->prepare('SELECT pid FROM ' . Database::quoteIdentifier($strTable) . ' WHERE id=?')
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
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public function addCtableTags($strTable, $intId, &$tags)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Calling "%s()" has been deprecated and will no longer work in Contao 5.0.', __METHOD__);

		$ctables = $GLOBALS['TL_DCA'][$strTable]['config']['ctable'] ?? array();

		if (($GLOBALS['TL_DCA'][$strTable]['list']['sorting']['mode'] ?? null) == 5)
		{
			$ctables[] = $strTable;
		}

		if (!$ctables)
		{
			return;
		}

		foreach ($ctables as $ctable)
		{
			Controller::loadDataContainer($ctable);

			if ($GLOBALS['TL_DCA'][$ctable]['config']['dynamicPtable'] ?? null)
			{
				$objIds = $this->Database->prepare('SELECT id FROM ' . Database::quoteIdentifier($ctable) . ' WHERE pid=? AND ptable=?')
										 ->execute($intId, $strTable);
			}
			else
			{
				$objIds = $this->Database->prepare('SELECT id FROM ' . Database::quoteIdentifier($ctable) . ' WHERE pid=?')
										 ->execute($intId);
			}

			if (!$objIds->numRows)
			{
				continue;
			}

			while ($objIds->next())
			{
				$tags[] = 'contao.db.' . $ctable . '.' . $objIds->id;

				$this->addCtableTags($ctable, $objIds->id, $tags);
			}
		}
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
	 *
	 * @todo Change the return type to ?string in Contao 5.0
	 */
	public static function getDriverForTable(string $table): string
	{
		if (!isset($GLOBALS['TL_DCA'][$table]['config']['dataContainer']))
		{
			return '';
		}

		$dataContainer = $GLOBALS['TL_DCA'][$table]['config']['dataContainer'];

		if ('' !== $dataContainer && false === strpos($dataContainer, '\\'))
		{
			trigger_deprecation('contao/core-bundle', '4.9', 'The usage of a non fully qualified class name "%s" for table "%s" as DataContainer name has been deprecated and will no longer work in Contao 5.0. Use the fully qualified class name instead, e.g. Contao\DC_Table::class.', $dataContainer, $table);

			$dataContainer = 'DC_' . $dataContainer;

			if (class_exists($dataContainer))
			{
				$ref = new \ReflectionClass($dataContainer);

				return $ref->getName();
			}
		}

		return $dataContainer;
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
	public function generateRecordLabel(array $row, string $table = null, bool $protected = false, bool $isVisibleRootTrailPage = false)
	{
		$table = $table ?? $this->strTable;
		$labelConfig = &$GLOBALS['TL_DCA'][$table]['list']['label'];
		$args = array();

		foreach ($labelConfig['fields'] as $k=>$v)
		{
			// Decrypt the value
			if ($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['encrypt'] ?? null)
			{
				$row[$v] = Encryption::decrypt(StringUtil::deserialize($row[$v]));
			}

			if (strpos($v, ':') !== false)
			{
				list($strKey, $strTable) = explode(':', $v, 2);
				list($strTable, $strField) = explode('.', $strTable, 2);

				$objRef = Database::getInstance()
					->prepare("SELECT " . Database::quoteIdentifier($strField) . " FROM " . $strTable . " WHERE id=?")
					->limit(1)
					->execute($row[$strKey]);

				$args[$k] = $objRef->numRows ? $objRef->$strField : '';
			}
			elseif (\in_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['flag'] ?? null, array(self::SORT_DAY_ASC, self::SORT_DAY_DESC, self::SORT_MONTH_ASC, self::SORT_MONTH_DESC, self::SORT_YEAR_ASC, self::SORT_YEAR_DESC)))
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
				$args[$k] = $row[$v] ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
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

					$args[$k] = implode(', ', $args_k);
				}
				elseif (isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]]))
				{
					$args[$k] = \is_array($GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]]) ? $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]][0] : $GLOBALS['TL_DCA'][$table]['fields'][$v]['reference'][$row[$v]];
				}
				elseif ((($GLOBALS['TL_DCA'][$table]['fields'][$v]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$table]['fields'][$v]['options'] ?? null)) && isset($GLOBALS['TL_DCA'][$table]['fields'][$v]['options'][$row[$v]]))
				{
					$args[$k] = $GLOBALS['TL_DCA'][$table]['fields'][$v]['options'][$row[$v]] ?? null;
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
		$label = preg_replace('/<[^\/][^>]+>\s*<\/[^>]+>/', '', $label);

		$mode = $GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? self::MODE_SORTED;

		// Execute label_callback
		if (\is_array($labelConfig['label_callback'] ?? null) || \is_callable($labelConfig['label_callback'] ?? null))
		{
			if (\in_array($mode, array(self::MODE_TREE, self::MODE_TREE_EXTENDED)))
			{
				if (\is_array($labelConfig['label_callback'] ?? null))
				{
					$label = System::importStatic($labelConfig['label_callback'][0])->{$labelConfig['label_callback'][1]}($row, $label, $this, '', false, $protected, $isVisibleRootTrailPage);
				}
				else
				{
					$label = $labelConfig['label_callback']($row, $label, $this, '', false, $protected, $isVisibleRootTrailPage);
				}
			}
			elseif ($mode === self::MODE_PARENT)
			{
				if (\is_array($labelConfig['label_callback'] ?? null))
				{
					$label = System::importStatic($labelConfig['label_callback'][0])->{$labelConfig['label_callback'][1]}($row, $label, $this);
				}
				else
				{
					$label = $labelConfig['label_callback']($row, $label, $this);
				}
			}
			else
			{
				if (\is_array($labelConfig['label_callback'] ?? null))
				{
					$label = System::importStatic($labelConfig['label_callback'][0])->{$labelConfig['label_callback'][1]}($row, $label, $this, $args);
				}
				else
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
}

class_alias(DataContainer::class, 'DataContainer');
