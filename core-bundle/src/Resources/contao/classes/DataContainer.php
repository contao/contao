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
use Contao\Image\ResizeConfiguration;
use FOS\HttpCacheBundle\CacheManager;
use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Provide methods to handle data container arrays.
 *
 * @property integer $id
 * @property string  $table
 * @property mixed   $value
 * @property string  $field
 * @property string  $inputName
 * @property string  $palette
 * @property object  $activeRecord
 * @property array   $rootIds
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class DataContainer extends Backend
{
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
	 * IDs of all root records
	 * @var array
	 */
	protected $root;

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
	 * @param string $strPalette
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 * @throws \Exception
	 */
	protected function row($strPalette=null)
	{
		$arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField];

		// Check if the field is excluded
		if ($arrData['exclude'])
		{
			throw new AccessDeniedException('Field "' . $this->strTable . '.' . $this->strField . '" is excluded from being edited.');
		}

		$xlabel = '';

		// Toggle line wrap (textarea)
		if ($arrData['inputType'] == 'textarea' && !isset($arrData['eval']['rte']))
		{
			$xlabel .= ' ' . Image::getHtml('wrap.svg', $GLOBALS['TL_LANG']['MSC']['wordWrap'], 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['wordWrap']) . '" class="toggleWrap" onclick="Backend.toggleWrap(\'ctrl_' . $this->strInputName . '\')"');
		}

		// Add the help wizard
		if ($arrData['eval']['helpwizard'])
		{
			$xlabel .= ' <a href="contao/help.php?table=' . $this->strTable . '&amp;field=' . $this->strField . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['helpWizard']) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $arrData['label'][0])) . '\',\'url\':this.href});return false">' . Image::getHtml('about.svg', $GLOBALS['TL_LANG']['MSC']['helpWizard']) . '</a>';
		}

		// Add a custom xlabel
		if (\is_array($arrData['xlabel']))
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
		if (\is_array($arrData['input_field_callback']))
		{
			$this->import($arrData['input_field_callback'][0]);

			return $this->{$arrData['input_field_callback'][0]}->{$arrData['input_field_callback'][1]}($this, $xlabel);
		}

		if (\is_callable($arrData['input_field_callback']))
		{
			return $arrData['input_field_callback']($this, $xlabel);
		}

		$strClass = $GLOBALS['BE_FFL'][$arrData['inputType']];

		// Return if the widget class does not exists
		if (!class_exists($strClass))
		{
			return '';
		}

		$arrData['eval']['required'] = false;

		if ($arrData['eval']['mandatory'])
		{
			if (\is_array($this->varValue))
			{
				if (empty($this->varValue))
				{
					$arrData['eval']['required'] = true;
				}
			}
			// Use strlen() here (see #3277)
			elseif (!\strlen($this->varValue))
			{
				$arrData['eval']['required'] = true;
			}
		}

		// Convert insert tags in src attributes (see #5965)
		if (isset($arrData['eval']['rte']) && strncmp($arrData['eval']['rte'], 'tiny', 4) === 0)
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
				@trigger_error('Using FORM_FIELDS has been deprecated and will no longer work in Contao 5.0. Make sure to always submit at least an empty string in your widget.', E_USER_DEPRECATED);
			}

			// Validate and save the field
			if (\in_array($this->strInputName, $paletteFields) || Input::get('act') == 'overrideAll')
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
		if ($arrData['eval']['datepicker'])
		{
			$rgxp = $arrData['eval']['rgxp'];
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
			if ($arrData['eval']['submitOnChange'])
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
		if ($arrData['eval']['colorpicker'])
		{
			// Support single fields as well (see #5240)
			$strKey = $arrData['eval']['multiple'] ? $this->strField . '_0' : $this->strField;

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

		$arrClasses = StringUtil::trimsplit(' ', (string) $arrData['eval']['tl_class']);

		// DCA picker
		if (isset($arrData['eval']['dcaPicker']) && (\is_array($arrData['eval']['dcaPicker']) || $arrData['eval']['dcaPicker'] === true))
		{
			$arrClasses[] = 'dcapicker';
			$wizard .= Backend::getDcaPickerWizard($arrData['eval']['dcaPicker'], $this->strTable, $this->strField, $this->strInputName);
		}

		// Add a custom wizard
		if (\is_array($arrData['wizard']))
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

		if ($wizard && !($arrData['eval']['disabled'] ?? false) && !($arrData['eval']['readonly'] ?? false))
		{
			$objWidget->wizard = $wizard;

			if ($arrData['eval']['addWizardClass'] !== false && !\in_array('wizard', $arrClasses))
			{
				$arrClasses[] = 'wizard';
			}
		}
		elseif (\in_array('wizard', $arrClasses))
		{
			unset($arrClasses[array_search('wizard', $arrClasses)]);
		}

		// Set correct form enctype
		if ($objWidget instanceof \uploadable)
		{
			$this->blnUploadable = true;
		}

		if ($arrData['inputType'] != 'password')
		{
			$arrClasses[] = 'widget';
		}

		// Mark floated single checkboxes
		if ($arrData['inputType'] == 'checkbox' && !$arrData['eval']['multiple'] && \in_array('w50', $arrClasses))
		{
			$arrClasses[] = 'cbx';
		}
		elseif ($arrData['inputType'] == 'text' && $arrData['eval']['multiple'] && \in_array('wizard', $arrClasses))
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
			list ($file, $type) = explode('|', $arrData['eval']['rte'], 2);

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
		elseif (($arrData['inputType'] == 'checkbox' || $arrData['inputType'] == 'checkboxWizard') && $arrData['eval']['multiple'] && Input::get('act') == 'overrideAll')
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
						$image = rawurldecode($container->get('contao.image.image_factory')->create($projectDir . '/' . $objFile->path, array(699, 524, ResizeConfiguration::MODE_BOX))->getUrl($projectDir));
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
<div' . ($arrData['eval']['tl_class'] ? ' class="' . trim($arrData['eval']['tl_class']) . '"' : '') . '>' . $objWidget->parse() . $updateMode . (!$objWidget->hasErrors() ? $this->help($strHelpClass) : '') . '
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
		$return = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][1];

		if (!$return || $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] == 'password' || !Config::get('showHelp'))
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
		if (empty($GLOBALS['TL_DCA'][$strTable]['list']['operations']) || !\is_array($GLOBALS['TL_DCA'][$strTable]['list']['operations']))
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

			// Call a custom function instead of using the default button
			if (\is_array($v['button_callback']))
			{
				$this->import($v['button_callback'][0]);
				$return .= $this->{$v['button_callback'][0]}->{$v['button_callback'][1]}($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $this);
				continue;
			}

			if (\is_callable($v['button_callback']))
			{
				$return .= $v['button_callback']($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $this);
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
						$href = $this->addToUrl($v['href'] . '&amp;id=' . $arrRow['id'] . '&amp;popup=1');
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
						$href = $this->addToUrl($v['href'] . '&amp;id=' . $arrRow['id'] . (Input::get('nb') ? '&amp;nc=1' : ''));
					}

					$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($v['icon'], $label) . '</a> ';
				}

				continue;
			}

			$arrDirections = array('up', 'down');
			$arrRootIds = \is_array($arrRootIds) ? $arrRootIds : array($arrRootIds);

			foreach ($arrDirections as $dir)
			{
				$label = $GLOBALS['TL_LANG'][$strTable][$dir][0] ?: $dir;
				$title = $GLOBALS['TL_LANG'][$strTable][$dir][1] ?: $dir;

				$label = Image::getHtml($dir . '.svg', $label);
				$href = $v['href'] ?: '&amp;act=move';

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
		if (empty($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations']) || !\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations']))
		{
			return '';
		}

		$return = '';

		foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations'] as $k=>$v)
		{
			if (!$v['showOnSelect'] && Input::get('act') == 'select')
			{
				continue;
			}

			$v = \is_array($v) ? $v : array($v);
			$title = $label = $k;

			if (isset($v['label']))
			{
				$label = \is_array($v['label']) ? $v['label'][0] : $v['label'];
				$title = \is_array($v['label']) ? $v['label'][1] : $v['label'];
			}

			$attributes = !empty($v['attributes']) ? ' ' . ltrim($v['attributes']) : '';

			// Custom icon (see #5541)
			if ($v['icon'])
			{
				$v['class'] = trim($v['class'] . ' header_icon');

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
			if (\is_array($v['button_callback']))
			{
				$this->import($v['button_callback'][0]);
				$return .= $this->{$v['button_callback'][0]}->{$v['button_callback'][1]}($v['href'], $label, $title, $v['class'], $attributes, $this->strTable, $this->root);
				continue;
			}

			if (\is_callable($v['button_callback']))
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
				$href = $this->addToUrl($v['href']);
			}

			$return .= '<a href="' . $href . '" class="' . $v['class'] . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ';
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
		if (empty($GLOBALS['TL_DCA'][$strPtable]['list']['operations']) || !\is_array($GLOBALS['TL_DCA'][$strPtable]['list']['operations']))
		{
			return '';
		}

		$return = '';

		foreach ($GLOBALS['TL_DCA'][$strPtable]['list']['operations'] as $k=> $v)
		{
			if (empty($v['showInHeader']) || (Input::get('act') == 'select' && !$v['showOnSelect']))
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
			if (\is_array($v['button_callback']))
			{
				$this->import($v['button_callback'][0]);
				$return .= $this->{$v['button_callback'][0]}->{$v['button_callback'][1]}($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strPtable, array(), null, false, null, null, $this);
				continue;
			}

			if (\is_callable($v['button_callback']))
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

				$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", sprintf(\is_array($GLOBALS['TL_LANG'][$strPtable]['show']) ? $GLOBALS['TL_LANG'][$strPtable]['show'][1] : $GLOBALS['TL_LANG'][$strPtable]['show'], $arrRow['id']))) . '\',\'url\':this.href});return false"' . $attributes . '>' . Image::getHtml($v['icon'], $label) . '</a> ';
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

				$return .= '<a href="' . $href . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($v['icon'], $label) . '</a> ';
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
				return ' <input type="checkbox" name="picker[]" id="picker_' . $id . '" class="tl_tree_checkbox" value="' . StringUtil::specialchars(\call_user_func($this->objPickerCallback, $value)) . '" onfocus="Backend.getScrollOffset()"' . Widget::optionChecked($value, $this->arrPickerValue) . $attributes . '>';

			case 'radio':
				return ' <input type="radio" name="picker" id="picker_' . $id . '" class="tl_tree_radio" value="' . StringUtil::specialchars(\call_user_func($this->objPickerCallback, $value)) . '" onfocus="Backend.getScrollOffset()"' . Widget::optionChecked($value, $this->arrPickerValue) . $attributes . '>';
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
		if (!$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'])
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
		$arrPanes = StringUtil::trimsplit(';', $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout']);

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
						$arrCallback = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panel_callback'][$strSubPanel];

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
		if (\is_array($GLOBALS['TL_DCA'][$this->table]['config']['oninvalidate_cache_tags_callback']))
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

		/** @var CacheManager $cacheManager */
		$cacheManager = System::getContainer()->get('fos_http_cache.cache_manager');
		$cacheManager->invalidateTags($tags);
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

			if ($GLOBALS['TL_DCA'][$ctable]['config']['dynamicPtable'])
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
	 */
	public static function getDriverForTable(string $table): string
	{
		$dataContainer = $GLOBALS['TL_DCA'][$table]['config']['dataContainer'] ?? '';

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
}

class_alias(DataContainer::class, 'DataContainer');
