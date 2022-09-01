<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Provide methods to edit the local configuration file.
 */
class DC_File extends DataContainer implements EditableDataContainerInterface
{
	/**
	 * Initialize the object
	 *
	 * @param string $strTable
	 */
	public function __construct($strTable)
	{
		parent::__construct();

		$this->intId = Input::get('id');

		// Check whether the table is defined
		if (!$strTable || !isset($GLOBALS['TL_DCA'][$strTable]))
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Could not load data container configuration for "' . $strTable . '"');
			trigger_error('Could not load data container configuration', E_USER_ERROR);
		}

		// Build object from global configuration array
		$this->strTable = $strTable;

		// Call onload_callback (e.g. to check permissions)
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($this);
				}
				elseif (\is_callable($callback))
				{
					$callback($this);
				}
			}
		}
	}

	/**
	 * Automatically switch to edit mode
	 *
	 * @return string
	 */
	public function create()
	{
		return $this->edit();
	}

	/**
	 * Automatically switch to edit mode
	 *
	 * @return string
	 */
	public function cut()
	{
		return $this->edit();
	}

	/**
	 * Automatically switch to edit mode
	 *
	 * @return string
	 */
	public function copy()
	{
		return $this->edit();
	}

	/**
	 * Automatically switch to edit mode
	 *
	 * @return string
	 */
	public function move()
	{
		return $this->edit();
	}

	/**
	 * Auto-generate a form to edit the local configuration file
	 *
	 * @return string
	 */
	public function edit()
	{
		$return = '';
		$ajaxId = null;

		if (Environment::get('isAjaxRequest'))
		{
			$ajaxId = func_get_arg(1);
		}

		// Build an array from boxes and rows
		$this->strPalette = $this->getPalette();
		$boxes = StringUtil::trimsplit(';', $this->strPalette);
		$legends = array();

		if (!empty($boxes))
		{
			foreach ($boxes as $k=>$v)
			{
				$boxes[$k] = StringUtil::trimsplit(',', $v);

				foreach ($boxes[$k] as $kk=>$vv)
				{
					if (preg_match('/^\[.*]$/', $vv))
					{
						continue;
					}

					if (preg_match('/^{.*}$/', $vv))
					{
						$legends[$k] = substr($vv, 1, -1);
						unset($boxes[$k][$kk]);
					}
					elseif (!\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv] ?? null))
					{
						unset($boxes[$k][$kk]);
					}
				}

				// Unset a box if it does not contain any fields
				if (empty($boxes[$k]))
				{
					unset($boxes[$k]);
				}
			}

			/** @var AttributeBagInterface $objSessionBag */
			$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

			// Render boxes
			$class = 'tl_tbox';
			$fs = $objSessionBag->get('fieldset_states');

			foreach ($boxes as $k=>$v)
			{
				$strAjax = '';
				$blnAjax = false;
				$key = '';
				$cls = '';
				$legend = '';

				if (isset($legends[$k]))
				{
					list($key, $cls) = explode(':', $legends[$k]) + array(null, null);

					$legend = "\n" . '<legend data-toggle-fieldset="' . StringUtil::specialcharsAttribute(json_encode(array('id' => $key, 'table' => $this->strTable))) . '">' . ($GLOBALS['TL_LANG'][$this->strTable][$key] ?? $key) . '</legend>';
				}

				if (isset($fs[$this->strTable][$key]))
				{
					$class .= ($fs[$this->strTable][$key] ? '' : ' collapsed');
				}
				else
				{
					$class .= (($cls && $legend) ? ' ' . $cls : '');
				}

				$return .= "\n\n" . '<fieldset' . ($key ? ' id="pal_' . $key . '"' : '') . ' class="' . $class . ($legend ? '' : ' nolegend') . '">' . $legend;

				// Build rows of the current box
				foreach ($v as $vv)
				{
					if ($vv == '[EOF]')
					{
						if ($blnAjax && Environment::get('isAjaxRequest'))
						{
							return $strAjax;
						}

						$blnAjax = false;
						$return .= "\n  " . '</div>';

						continue;
					}

					if (preg_match('/^\[.*]$/', $vv))
					{
						$thisId = 'sub_' . substr($vv, 1, -1);
						$blnAjax = ($ajaxId == $thisId && Environment::get('isAjaxRequest'));
						$return .= "\n  " . '<div id="' . $thisId . '" class="subpal cf">';

						continue;
					}

					$this->strField = $vv;
					$this->strInputName = $vv;
					$this->varValue = Config::get($this->strField);

					// Handle entities
					if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] ?? null) == 'text' || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['inputType'] ?? null) == 'textarea')
					{
						if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple'] ?? null)
						{
							$this->varValue = StringUtil::deserialize($this->varValue);
						}
					}

					// Call load_callback
					if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] ?? null))
					{
						foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
						{
							if (\is_array($callback))
							{
								$this->import($callback[0]);
								$this->varValue = $this->{$callback[0]}->{$callback[1]}($this->varValue, $this);
							}
							elseif (\is_callable($callback))
							{
								$this->varValue = $callback($this->varValue, $this);
							}
						}
					}

					// Build row
					$blnAjax ? $strAjax .= $this->row() : $return .= $this->row();
				}

				$class = 'tl_box';
				$return .= "\n" . '</fieldset>';
			}
		}

		$configFile = Path::join(System::getContainer()->getParameter('kernel.project_dir'), 'system/config/localconfig.php');

		// Check whether the target file is writeable
		if (file_exists($configFile) && !is_writeable($configFile))
		{
			Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['notWriteable'], 'system/config/localconfig.php'));
		}

		// Submit buttons
		$arrButtons = array();
		$arrButtons['save'] = '<button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['MSC']['save'] . '</button>';
		$arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c">' . $GLOBALS['TL_LANG']['MSC']['saveNclose'] . '</button>';

		// Call the buttons_callback (see #4691)
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$arrButtons = $this->{$callback[0]}->{$callback[1]}($arrButtons, $this);
				}
				elseif (\is_callable($callback))
				{
					$arrButtons = $callback($arrButtons, $this);
				}
			}
		}

		// Add the buttons and end the form
		$return .= '
</div>
<div class="tl_formbody_submit">
<div class="tl_submit_container">
  ' . implode(' ', $arrButtons) . '
</div>
</div>
</form>';

		// Begin the form (-> DO NOT CHANGE THIS ORDER -> this way the onsubmit attribute of the form can be changed by a field)
		$return = Message::generate() . ($this->noReload ? '
<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['general'] . '</p>' : '') . '
<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
<form id="' . $this->strTable . '" class="tl_form tl_edit_form" method="post"' . (!empty($this->onsubmit) ? ' onsubmit="' . implode(' ', $this->onsubmit) . '"' : '') . '>
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="' . $this->strTable . '">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">' . $return;

		// Reload the page to prevent _POST variables from being sent twice
		if (!$this->noReload && Input::post('FORM_SUBMIT') == $this->strTable)
		{
			// Call onsubmit_callback
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						$this->import($callback[0]);
						$this->{$callback[0]}->{$callback[1]}($this);
					}
					elseif (\is_callable($callback))
					{
						$callback($this);
					}
				}
			}

			// Reload
			if (Input::post('saveNclose') !== null)
			{
				Message::reset();
				$this->redirect($this->getReferer());
			}

			$this->reload();
		}

		// Set the focus if there is an error
		if ($this->noReload)
		{
			$return .= '
<script>
  window.addEvent(\'domready\', function() {
    Backend.vScrollTo(($(\'' . $this->strTable . '\').getElement(\'label.error\').getPosition().y - 20));
  });
</script>';
		}

		return $return;
	}

	/**
	 * Save the current value
	 *
	 * @param mixed $varValue
	 */
	protected function save($varValue)
	{
		if (Input::post('FORM_SUBMIT') != $this->strTable)
		{
			return;
		}

		$arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField] ?? array();

		// Make sure that checkbox values are boolean
		if (($arrData['inputType'] ?? null) == 'checkbox' && !($arrData['eval']['multiple'] ?? null))
		{
			$varValue = $varValue ? true : false;
		}

		if ($varValue)
		{
			// Convert binary UUIDs (see #6893)
			if (($arrData['inputType'] ?? null) == 'fileTree')
			{
				$varValue = StringUtil::deserialize($varValue);

				if (!\is_array($varValue))
				{
					$varValue = StringUtil::binToUuid($varValue);
				}
				else
				{
					$varValue = serialize(array_map('\Contao\StringUtil::binToUuid', $varValue));
				}
			}

			// Convert date formats into timestamps
			if ($varValue !== null && $varValue !== '' && \in_array($arrData['eval']['rgxp'] ?? null, array('date', 'time', 'datim')))
			{
				$objDate = new Date($varValue, Date::getFormatFromRgxp($arrData['eval']['rgxp']));
				$varValue = $objDate->tstamp;
			}

			// Handle entities
			if (($arrData['inputType'] ?? null) == 'text' || ($arrData['inputType'] ?? null) == 'textarea')
			{
				$varValue = StringUtil::deserialize($varValue);
			}
		}

		// Trigger the save_callback
		if (\is_array($arrData['save_callback'] ?? null))
		{
			foreach ($arrData['save_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $this);
				}
				elseif (\is_callable($callback))
				{
					$varValue = $callback($varValue, $this);
				}
			}
		}

		$strCurrent = $this->varValue;

		// Handle arrays and strings
		if (\is_array($strCurrent))
		{
			$strCurrent = serialize($strCurrent);
		}
		elseif (\is_string($strCurrent))
		{
			$strCurrent = html_entity_decode($this->varValue, ENT_QUOTES, System::getContainer()->getParameter('kernel.charset'));
		}

		// Save the value if there was no error
		if ($strCurrent != $varValue && (\strlen($varValue) || !($arrData['eval']['doNotSaveEmpty'] ?? null)))
		{
			Config::persist($this->strField, $varValue);

			$deserialize = StringUtil::deserialize($varValue);
			$prior = \is_bool(Config::get($this->strField)) ? (Config::get($this->strField) ? 'true' : 'false') : Config::get($this->strField);

			// Add a log entry
			if (!\is_array($deserialize) && !\is_array(StringUtil::deserialize($prior)))
			{
				if (($arrData['inputType'] ?? null) == 'password')
				{
					System::getContainer()->get('monolog.logger.contao.configuration')->info('The global configuration variable "' . $this->strField . '" has been changed');
				}
				else
				{
					System::getContainer()->get('monolog.logger.contao.configuration')->info('The global configuration variable "' . $this->strField . '" has been changed from "' . $prior . '" to "' . $varValue . '"');
				}
			}

			// Set the new value so the input field can show it
			$this->varValue = $deserialize;
			Config::set($this->strField, $deserialize);
		}
	}

	/**
	 * Return the name of the current palette
	 *
	 * @return string
	 */
	public function getPalette()
	{
		$strPalette = $GLOBALS['TL_DCA'][$this->strTable]['palettes']['default'] ?? '';

		// Check whether there are selector fields
		if (!empty($GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__']))
		{
			$sValues = array();
			$subpalettes = array();

			foreach ($GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__'] as $name)
			{
				$trigger = Config::get($name);

				// Overwrite the trigger if the page is not reloaded
				if (Input::post('FORM_SUBMIT') == $this->strTable)
				{
					$key = (Input::get('act') == 'editAll') ? $name . '_' . $this->intId : $name;

					if (!($GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['eval']['submitOnChange'] ?? null))
					{
						$trigger = Input::post($key);
					}
				}

				if ($trigger)
				{
					if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['inputType'] ?? null) == 'checkbox' && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['eval']['multiple'] ?? null))
					{
						$sValues[] = $name;

						// Look for a subpalette
						if (isset($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$name]))
						{
							$subpalettes[$name] = $GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$name];
						}
					}
					else
					{
						$sValues[] = $trigger;
						$key = $name . '_' . $trigger;

						// Look for a subpalette
						if (isset($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$key]))
						{
							$subpalettes[$name] = $GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$key];
						}
					}
				}
			}

			// Build possible palette names from the selector values
			if (empty($sValues))
			{
				$names = array('default');
			}
			elseif (\count($sValues) > 1)
			{
				$names = $this->combiner($sValues);
			}
			else
			{
				$names = array($sValues[0]);
			}

			// Get an existing palette
			foreach ($names as $paletteName)
			{
				if (isset($GLOBALS['TL_DCA'][$this->strTable]['palettes'][$paletteName]))
				{
					$strPalette = $GLOBALS['TL_DCA'][$this->strTable]['palettes'][$paletteName];
					break;
				}
			}

			// Include subpalettes
			foreach ($subpalettes as $k=>$v)
			{
				$strPalette = preg_replace('/\b' . preg_quote($k, '/') . '\b/i', $k . ',[' . $k . '],' . $v . ',[EOF]', $strPalette);
			}
		}

		return $strPalette;
	}
}
