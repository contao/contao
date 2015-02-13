<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;


/**
 * Loads modules based on their autoload.ini configuration
 *
 * The class reads the autoload.ini files of the available modules and returns
 * an array of active modules with their dependencies solved.
 *
 * Usage:
 *
 *     $arrModules = ModuleLoader::getActive();
 *     $arrModules = ModuleLoader::getDisabled();
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleLoader
{

	/**
	 * Active modules
	 * @var array
	 */
	protected static $active = array();

	/**
	 * Disabled modules
	 * @var array
	 */
	protected static $disabled = array();


	/**
	 * Return the active modules as array
	 *
	 * @return array An array of active modules
	 */
	public static function getActive()
	{
		if (empty(static::$active))
		{
			global $kernel;

			foreach ($kernel->getContaoBundles() as $bundle)
			{
				static::$active[] = $bundle->getName();
			}
		}

		return static::$active;
	}


	/**
	 * Return the disabled modules as array
	 *
	 * @return array An array of disabled modules
	 */
	public static function getDisabled()
	{
		return array();
	}
}
