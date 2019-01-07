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
 * Automatically loads class files based on a mapper array
 *
 * The class stores namespaces and classes and automatically loads the class
 * files upon their first usage. It uses a mapper array to support complex
 * nesting and arbitrary subfolders to store the class files in.
 *
 * Usage:
 *
 *     ClassLoader::addNamespace('Custom');
 *     ClassLoader::addClass('Custom\Calendar', 'calendar/Calendar.php');
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
 *             Use the Composer autoloader instead.
 */
class ClassLoader
{

	/**
	 * Known namespaces
	 * @var array
	 */
	protected static $namespaces = array('Contao');

	/**
	 * Known classes
	 * @var array
	 */
	protected static $classes = array();

	/**
	 * Add a new namespace
	 *
	 * @param string $name The namespace name
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
	 */
	public static function addNamespace($name)
	{
		@trigger_error('Using ClassLoader::addNamespace() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		if (\in_array($name, self::$namespaces))
		{
			return;
		}

		array_unshift(self::$namespaces, $name);
	}

	/**
	 * Add multiple new namespaces
	 *
	 * @param array $names An array of namespace names
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
	 */
	public static function addNamespaces($names)
	{
		@trigger_error('Using ClassLoader::addNamespaces() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		foreach ($names as $name)
		{
			self::addNamespace($name);
		}
	}

	/**
	 * Return the namespaces as array
	 *
	 * @return array An array of all namespaces
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
	 */
	public static function getNamespaces()
	{
		@trigger_error('Using ClassLoader::getNamespaces() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		return self::$namespaces;
	}

	/**
	 * Add a new class with its file path
	 *
	 * @param string $class The class name
	 * @param string $file  The path to the class file
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
	 */
	public static function addClass($class, $file)
	{
		@trigger_error('Using ClassLoader::addClass() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		self::$classes[$class] = $file;
	}

	/**
	 * Add multiple new classes with their file paths
	 *
	 * @param array $classes An array of classes
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
	 */
	public static function addClasses($classes)
	{
		@trigger_error('Using ClassLoader::addClasses() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		foreach ($classes as $class=>$file)
		{
			self::addClass($class, $file);
		}
	}

	/**
	 * Return the classes as array.
	 *
	 * @return array An array of all classes
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
	 */
	public static function getClasses()
	{
		@trigger_error('Using ClassLoader::getClasses() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		return self::$classes;
	}

	/**
	 * Autoload a class and create an alias in the global namespace
	 *
	 * To preserve backwards compatibility with Contao 2 extensions, all core
	 * classes will be aliased into the global namespace.
	 *
	 * @param string $class The class name
	 */
	public static function load($class)
	{
		if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false))
		{
			return;
		}

		$rootDir = System::getContainer()->getParameter('kernel.project_dir');

		// The class file is set in the mapper
		if (isset(self::$classes[$class]))
		{
			if (Config::get('debugMode'))
			{
				$GLOBALS['TL_DEBUG']['classes_set'][$class] = $class;
			}

			include $rootDir . '/' . self::$classes[$class];
		}

		// Find the class in the registered namespaces
		elseif (($namespaced = self::findClass($class)) !== null)
		{
			if (!class_exists($namespaced, false) && !interface_exists($namespaced, false) && !trait_exists($namespaced, false))
			{
				if (Config::get('debugMode'))
				{
					$GLOBALS['TL_DEBUG']['classes_aliased'][$class] = $namespaced;
				}

				include $rootDir . '/' . self::$classes[$namespaced];
			}

			class_alias($namespaced, $class);
		}

		// Try to map the class to a Contao class loaded via Composer
		elseif (strncmp($class, 'Contao\\', 7) !== 0)
		{
			$namespaced = 'Contao\\' . $class;

			if (class_exists($namespaced) || interface_exists($namespaced) || trait_exists($namespaced))
			{
				if (Config::get('debugMode'))
				{
					$GLOBALS['TL_DEBUG']['classes_composerized'][$class] = $namespaced;
				}

				if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false))
				{
					class_alias($namespaced, $class);
				}
			}
		}

		// Pass the request to other autoloaders (e.g. Swift)
	}

	/**
	 * Search the namespaces for a matching entry
	 *
	 * @param string $class The class name
	 *
	 * @return string|null The full path including the namespace or null
	 */
	protected static function findClass($class)
	{
		foreach (self::$namespaces as $namespace)
		{
			if (isset(self::$classes[$namespace . '\\' . $class]))
			{
				return $namespace . '\\' . $class;
			}
		}

		return null;
	}

	/**
	 * Register the autoloader
	 */
	public static function register()
	{
		spl_autoload_register('ClassLoader::load');
	}

	/**
	 * Scan the module directories for config/autoload.php files and then
	 * register the autoloader on the SPL stack
	 */
	public static function scanAndRegister()
	{
		$strCacheDir = System::getContainer()->getParameter('kernel.cache_dir');

		// Try to load from cache
		if (file_exists($strCacheDir . '/contao/config/autoload.php'))
		{
			include $strCacheDir . '/contao/config/autoload.php';
		}
		else
		{
			try
			{
				$files = System::getContainer()->get('contao.resource_locator')->locate('config/autoload.php', null, false);
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

		self::register();
	}
}

class_alias(ClassLoader::class, 'ClassLoader');
