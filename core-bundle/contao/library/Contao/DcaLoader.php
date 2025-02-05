<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Config\Dumper\CombinedFileDumper;
use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;

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
	 * @var \WeakMap<Request, array{array, array}>
	 */
	protected static \WeakMap $dcaByRequest;

	protected static Request $nullRequest;

	protected static Request|null $lastRequest = null;

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

	public static function reset(): void
	{
		self::$lastRequest = null;
		self::$dcaByRequest = new \WeakMap();
		self::$arrLoaded = array();

		unset($GLOBALS['TL_DCA']);
	}

	/**
	 * DCA loading depends on the current request. Switching the request sets or
	 * resets the global DCA array and makes it possible to (re)load a DCA once
	 * again.
	 *
	 * @internal
	 */
	public static function switchToCurrentRequest(): void
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if (self::$lastRequest === $request)
		{
			return;
		}

		self::$nullRequest ??= new Request();
		self::$dcaByRequest ??= new \WeakMap();
		self::$dcaByRequest->offsetSet(self::$lastRequest ?? self::$nullRequest, array($GLOBALS['TL_DCA'] ?? array(), self::$arrLoaded ?? array()));

		self::$lastRequest = $request;
		$request ??= self::$nullRequest;

		if (self::$dcaByRequest->offsetExists($request))
		{
			[$GLOBALS['TL_DCA'], self::$arrLoaded] = self::$dcaByRequest->offsetGet($request);
			self::$dcaByRequest->offsetUnset($request);
		}
		else
		{
			self::$arrLoaded = array();
			unset($GLOBALS['TL_DCA']);
		}
	}

	/**
	 * Load a set of DCA files
	 */
	public function load()
	{
		self::switchToCurrentRequest();

		// Return if the data has been loaded already
		if (isset(static::$arrLoaded['dcaFiles'][$this->strTable]))
		{
			// Throw the original exception if the first load failed
			if (static::$arrLoaded['dcaFiles'][$this->strTable] instanceof \Throwable)
			{
				throw static::$arrLoaded['dcaFiles'][$this->strTable];
			}

			return;
		}

		static::$arrLoaded['dcaFiles'][$this->strTable] = true; // see #6145

		try
		{
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
		$filesystem = new Filesystem();
		$strCacheDir = System::getContainer()->getParameter('kernel.cache_dir');
		$strCachePath = $strCacheDir . '/contao/dca/' . $this->strTable . '.php';

		// Try to load from cache
		if (file_exists($strCachePath) && !System::getContainer()->getParameter('kernel.debug'))
		{
			include $strCachePath;
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

			if ($files)
			{
				$dumper = new CombinedFileDumper($filesystem, new PhpFileLoader(), Path::join($strCacheDir, 'contao/dca'));
				$dumper->dump($files, $this->strTable . '.php', array('type' => 'namespaced'));

				try
				{
					include $strCachePath;
				}
				catch (\Throwable)
				{
					$filesystem->remove($strCachePath);
				}
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
