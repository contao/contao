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
 * Loads a set of DCA files
 *
 * The class loads the DCA files of a certain table and stores a combined
 * version in the cache directory.
 *
 * Usage:
 *
 *     $dca = new DcaLoader('tl_user');
 *     $dca->load();
 */
class DcaLoader extends Controller
{
	/**
	 * @var array
	 */
	protected static $arrLoaded = array();

	/**
	 * Table name
	 * @var string
	 */
	protected $strTable;

	/**
	 * Store the table name
	 *
	 * @param string $strTable The table name
	 *
	 * @throws \Exception If $strTable is empty
	 */
	public function __construct($strTable)
	{
		if (!$strTable)
		{
			throw new \Exception('The table name must not be empty');
		}

		if (Validator::isInsecurePath($strTable))
		{
			throw new \InvalidArgumentException('The table name contains invalid characters');
		}

		parent::__construct();

		$this->strTable = $strTable;
	}

	/**
	 * Load a set of DCA files
	 *
	 * @param boolean $blnNoCache If true, the cache will be bypassed
	 */
	public function load($blnNoCache=false)
	{
		if ($blnNoCache)
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Calling "%s" with $blnNoCache = true has been deprecated and will no longer work in Contao 5.0.', __METHOD__);
		}

		try
		{
			$this->loadDcaFiles($blnNoCache);
		}
		catch (\Throwable $e)
		{
			unset(static::$arrLoaded['dcaFiles'][$this->strTable]);

			throw $e;
		}
	}

	/**
	 * Load the DCA files
	 *
	 * @param boolean $blnNoCache
	 */
	private function loadDcaFiles($blnNoCache)
	{
		// Return if the data has been loaded already
		if (!$blnNoCache && isset(static::$arrLoaded['dcaFiles'][$this->strTable]))
		{
			return;
		}

		static::$arrLoaded['dcaFiles'][$this->strTable] = true; // see #6145

		$strCacheDir = System::getContainer()->getParameter('kernel.cache_dir');

		// Try to load from cache
		if (file_exists($strCacheDir . '/contao/dca/' . $this->strTable . '.php'))
		{
			include $strCacheDir . '/contao/dca/' . $this->strTable . '.php';
		}
		else
		{
			try
			{
				$files = System::getContainer()->get('contao.resource_locator')->locate('dca/' . $this->strTable . '.php', null, false);
			}
			catch (\InvalidArgumentException $e)
			{
				$files = array();
			}

			foreach ($files as $file)
			{
				include $file;
			}
		}

		// Set the ptable dynamically
		$this->setDynamicPTable();

		// HOOK: allow loading custom settings
		if (isset($GLOBALS['TL_HOOKS']['loadDataContainer']) && \is_array($GLOBALS['TL_HOOKS']['loadDataContainer']))
		{
			foreach ($GLOBALS['TL_HOOKS']['loadDataContainer'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($this->strTable);
			}
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Local configuration file
		if (file_exists($projectDir . '/system/config/dcaconfig.php'))
		{
			trigger_deprecation('contao/core-bundle', '4.3', 'Using the "dcaconfig.php" file has been deprecated and will no longer work in Contao 5.0. Create custom DCA files in the "contao/dca" folder instead.');
			include $projectDir . '/system/config/dcaconfig.php';
		}

		$this->addDefaultLabels($blnNoCache);
	}

	/**
	 * Add the default labels (see #509)
	 *
	 * @param boolean $blnNoCache
	 */
	private function addDefaultLabels($blnNoCache)
	{
		// Operations
		foreach (array('global_operations', 'operations') as $key)
		{
			if (!isset($GLOBALS['TL_DCA'][$this->strTable]['list'][$key]))
			{
				continue;
			}

			foreach ($GLOBALS['TL_DCA'][$this->strTable]['list'][$key] as $k=>&$v)
			{
				if (isset($v['label']))
				{
					continue;
				}

				if (isset($GLOBALS['TL_LANG'][$this->strTable][$k]) || !isset($GLOBALS['TL_LANG']['DCA'][$k]))
				{
					$v['label'] = &$GLOBALS['TL_LANG'][$this->strTable][$k];
				}
				elseif (isset($GLOBALS['TL_LANG']['DCA'][$k]))
				{
					$v['label'] = &$GLOBALS['TL_LANG']['DCA'][$k];
				}
			}

			unset($v);
		}

		// Fields
		if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields']))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>&$v)
			{
				if (isset($v['label']))
				{
					continue;
				}

				$v['label'] = &$GLOBALS['TL_LANG'][$this->strTable][$k];
			}

			unset($v);
		}
	}

	/**
	 * Sets the parent table for the current table, if enabled and not set.
	 */
	private function setDynamicPTable(): void
	{
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null) || !isset($GLOBALS['BE_MOD']))
		{
			return;
		}

		if (!$do = Input::get('do'))
		{
			return;
		}

		foreach (array_merge(...array_values($GLOBALS['BE_MOD'])) as $key => $module)
		{
			if ($do !== $key || !isset($module['tables']) || !\is_array($module['tables']))
			{
				continue;
			}

			foreach ($module['tables'] as $table)
			{
				Controller::loadDataContainer($table);
				$ctable = $GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? array();

				if (\in_array($this->strTable, $ctable, true))
				{
					$GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'] = $table;

					return;
				}
			}
		}
	}
}

class_alias(DcaLoader::class, 'DcaLoader');
