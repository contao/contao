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
	 */
	public function load()
	{
		// Return if the data has been loaded already
		if (isset(static::$arrLoaded['dcaFiles'][$this->strTable]))
		{
			// Throw the original exception if the first load failed
			if (static::$arrLoaded['dcaFiles'][$this->strTable] instanceof \Throwable)
			{
				throw static::$arrLoaded['dcaFiles'][$this->strTable];
			}

			if (!isset($GLOBALS['TL_DCA'][$this->strTable]))
			{
				trigger_deprecation('contao/core-bundle', '5.0', 'Loading a non-existent DCA "%s" has has been deprecated and will throw an exception in Contao 6.', $this->strTable);
			}

			return;
		}

		try
		{
			static::$arrLoaded['dcaFiles'][$this->strTable] = true; // see #6145

			$this->loadDcaFiles();
		}
		catch (\Throwable $e)
		{
			static::$arrLoaded['dcaFiles'][$this->strTable] = $e;

			throw $e;
		}

		if (!isset($GLOBALS['TL_DCA'][$this->strTable]))
		{
			trigger_deprecation('contao/core-bundle', '5.0', 'Loading a non-existent DCA "%s" has has been deprecated and will throw an exception in Contao 6.', $this->strTable);
		}
	}

	/**
	 * Load the DCA files
	 */
	private function loadDcaFiles()
	{
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
				System::importStatic($callback[0])->{$callback[1]}($this->strTable);
			}
		}

		$this->addDefaultLabels();
	}

	/**
	 * Add the default labels (see #509)
	 */
	private function addDefaultLabels()
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
				if (!\is_array($v) || \array_key_exists('label', $v))
				{
					continue;
				}

				if (isset($GLOBALS['TL_LANG'][$this->strTable][$k]) || !isset($GLOBALS['TL_LANG']['DCA'][$k]))
				{
					$v['label'] = &$GLOBALS['TL_LANG'][$this->strTable][$k];
				}
				else
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
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null) || !isset($GLOBALS['BE_MOD']) || isset($GLOBALS['TL_DCA'][$this->strTable]['config']['ptable']))
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
