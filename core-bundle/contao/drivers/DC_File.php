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
					System::importStatic($callback[0])->{$callback[1]}($this);
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

		$this->strPalette = $this->getPalette();
		$boxes = System::getContainer()->get('contao.data_container.palette_builder')->getBoxes($this->strPalette, $this->strTable);

		if (!empty($boxes))
		{
			// Render boxes
			$class = 'tl_tbox';

			foreach ($boxes as $box)
			{
				$strAjax = '';
				$blnAjax = false;
				$key = $box['key'];
				$legend = '';

				if ($key)
				{
					$legend = "\n" . '<legend><button type="button" data-action="contao--toggle-fieldset#toggle">' . ($GLOBALS['TL_LANG'][$this->strTable][$key] ?? $key) . '</button></legend>';

					if ($box['class'])
					{
						$class .= ' ' . $box['class'];
					}
				}

				$return .= "\n\n" . '<fieldset id="pal_' . $key . '" class="' . $class . ($legend ? '' : ' nolegend') . '" data-controller="contao--toggle-fieldset" data-contao--toggle-fieldset-id-value="' . $key . '" data-contao--toggle-fieldset-table-value="' . $this->strTable . '" data-contao--toggle-fieldset-collapsed-class="collapsed" data-contao--jump-targets-target="section" data-contao--jump-targets-label-value="' . ($GLOBALS['TL_LANG'][$this->strTable][$key] ?? $key) . '" data-action="contao--jump-targets:scrollto->contao--toggle-fieldset#open">' . $legend . "\n" . '<div class="widget-group">';

				// Build rows of the current box
				foreach ($box['fields'] as $vv)
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
						$blnAjax = $ajaxId == $thisId && Environment::get('isAjaxRequest');
						$return .= "\n  " . '<div id="' . $thisId . '" class="subpal widget-group">';

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
								$this->varValue = System::importStatic($callback[0])->{$callback[1]}($this->varValue, $this);
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
				$return .= "\n</div>\n</fieldset>";
			}
		}

		$configFile = Path::join(System::getContainer()->getParameter('kernel.project_dir'), 'system/config/localconfig.php');

		// Check whether the target file is writeable
		if (file_exists($configFile) && !is_writable($configFile))
		{
			Message::addError(\sprintf($GLOBALS['TL_LANG']['ERR']['notWriteable'], 'system/config/localconfig.php'));
		}

		$strButtons = System::getContainer()->get('contao.data_container.buttons_builder')->generateEditButtons($this->strTable, false, false, false, $this);

		// Add the buttons and end the form
		$return .= '
</div>
  ' . $strButtons . '
</form>';

		// Begin the form (-> DO NOT CHANGE THIS ORDER -> this way the onsubmit attribute of the form can be changed by a field)
		$return = Message::generate() . ($this->noReload ? '
<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['submit'] . '</p>' : '') . '
<form id="' . $this->strTable . '" class="tl_form tl_edit_form" method="post"' . (!empty($this->onsubmit) ? ' onsubmit="' . implode(' ', $this->onsubmit) . '"' : '') . '>
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="' . $this->strTable . '">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5) . '">' . $return;

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
						System::importStatic($callback[0])->{$callback[1]}($this);
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

		$return = '
<div data-controller="contao--jump-targets" data-contao--jump-targets-prev-label-value="' . $GLOBALS['TL_LANG']['MSC']['scrollLeft'] . '" data-contao--jump-targets-next-label-value="' . $GLOBALS['TL_LANG']['MSC']['scrollRight'] . '">
	<div class="jump-targets"><div class="inner" data-contao--jump-targets-target="navigation"></div></div>
	' . $return . '
</div>';

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
			$varValue = (bool) $varValue;
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
		}

		// Trigger the save_callback
		if (\is_array($arrData['save_callback'] ?? null))
		{
			foreach ($arrData['save_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$varValue = System::importStatic($callback[0])->{$callback[1]}($varValue, $this);
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
			$strCurrent = html_entity_decode($this->varValue, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, System::getContainer()->getParameter('kernel.charset'));
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
		return System::getContainer()
			->get('contao.data_container.palette_builder')
			->getPalette($this->strTable, (int) $this->intId, $this)
		;
	}

	public function getCurrentRecord(int|string|null $id = null, string|null $table = null): array|null
	{
		return $GLOBALS['TL_CONFIG'];
	}
}
