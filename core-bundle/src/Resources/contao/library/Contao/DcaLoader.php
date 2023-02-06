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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
		$this->loadDcaFiles($blnNoCache);
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

		// HOOK: allow to load custom settings
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
			@trigger_error('Using the "dcaconfig.php" file has been deprecated and will no longer work in Contao 5.0. Create custom DCA files in the "contao/dca" folder instead.', E_USER_DEPRECATED);
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
				if (\is_array($v) && \array_key_exists('label', $v))
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
}

class_alias(DcaLoader::class, 'DcaLoader');
