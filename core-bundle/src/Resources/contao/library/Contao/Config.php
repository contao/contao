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
 * Loads and writes the local configuration file
 *
 * Custom settings above or below the `### INSTALL SCRIPT ###` markers will be
 * preserved.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Config
{

	/**
	 * Object instance (Singleton)
	 * @var Config
	 */
	protected static $objInstance;

	/**
	 * Files object
	 * @var Files
	 */
	protected $Files;

	/**
	 * Top content
	 * @var string
	 */
	protected $strTop = '';

	/**
	 * Bottom content
	 * @var string
	 */
	protected $strBottom = '';

	/**
	 * Modification indicator
	 * @var boolean
	 */
	protected $blnIsModified = false;

	/**
	 * Local file existance
	 * @var boolean
	 */
	protected static $blnHasLcf;

	/**
	 * Data
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Cache
	 * @var array
	 */
	protected $arrCache = array();

	/**
	 * Root dir
	 * @var string
	 */
	protected $strRootDir;

	/**
	 * Prevent direct instantiation (Singleton)
	 */
	protected function __construct()
	{
		$this->strRootDir = System::getContainer()->getParameter('kernel.project_dir');
	}

	/**
	 * Automatically save the local configuration
	 */
	public function __destruct()
	{
		if ($this->blnIsModified)
		{
			$this->save();
		}
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone() {}

	/**
	 * Return the current object instance (Singleton)
	 *
	 * @return static The object instance
	 */
	public static function getInstance()
	{
		if (static::$objInstance === null)
		{
			static::$objInstance = new static();
			static::$objInstance->initialize();
		}

		return static::$objInstance;
	}

	/**
	 * Load all configuration files
	 */
	protected function initialize()
	{
		if (static::$blnHasLcf === null)
		{
			static::preload();
		}

		$strCacheDir = System::getContainer()->getParameter('kernel.cache_dir');

		if (file_exists($strCacheDir . '/contao/config/config.php'))
		{
			include $strCacheDir . '/contao/config/config.php';
		}
		else
		{
			try
			{
				$files = System::getContainer()->get('contao.resource_locator')->locate('config/config.php', null, false);
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

		// Include the local configuration file again
		if (static::$blnHasLcf)
		{
			include $this->strRootDir . '/system/config/localconfig.php';
		}

		static::loadParameters();
	}

	/**
	 * Mark the object as modified
	 */
	protected function markModified()
	{
		// Return if marked as modified already
		if ($this->blnIsModified === true)
		{
			return;
		}

		$this->blnIsModified = true;

		// Reset the top and bottom content (see #344)
		$this->strTop = '';
		$this->strBottom = '';

		// Import the Files object (required in the destructor)
		$this->Files = Files::getInstance();

		// Parse the local configuration file
		if (static::$blnHasLcf)
		{
			$strMode = 'top';
			$resFile = fopen($this->strRootDir . '/system/config/localconfig.php', 'rb');

			while (!feof($resFile))
			{
				$strLine = fgets($resFile);
				$strTrim = trim($strLine);

				if ($strTrim == '?>')
				{
					continue;
				}

				if ($strTrim == '### INSTALL SCRIPT START ###')
				{
					$strMode = 'data';
					continue;
				}

				if ($strTrim == '### INSTALL SCRIPT STOP ###')
				{
					$strMode = 'bottom';
					continue;
				}

				if ($strMode == 'top')
				{
					$this->strTop .= $strLine;
				}
				elseif ($strMode == 'bottom')
				{
					$this->strBottom .= $strLine;
				}
				elseif ($strTrim != '')
				{
					$arrChunks = array_map('trim', explode('=', $strLine, 2));
					$this->arrData[$arrChunks[0]] = $arrChunks[1];
				}
			}

			fclose($resFile);
		}
	}

	/**
	 * Save the local configuration file
	 */
	public function save()
	{
		if ($this->strTop == '')
		{
			$this->strTop = '<?php';
		}

		$strFile  = trim($this->strTop) . "\n\n";
		$strFile .= "### INSTALL SCRIPT START ###\n";

		foreach ($this->arrData as $k=>$v)
		{
			$strFile .= "$k = $v\n";
		}

		$strFile .= "### INSTALL SCRIPT STOP ###\n";
		$this->strBottom = trim($this->strBottom);

		if ($this->strBottom != '')
		{
			$strFile .= "\n" . $this->strBottom . "\n";
		}

		$strTemp = md5(uniqid(mt_rand(), true));

		// Write to a temp file first
		$objFile = fopen($this->strRootDir . '/system/tmp/' . $strTemp, 'wb');
		fwrite($objFile, $strFile);
		fclose($objFile);

		// Make sure the file has been written (see #4483)
		if (!filesize($this->strRootDir . '/system/tmp/' . $strTemp))
		{
			System::log('The local configuration file could not be written. Have your reached your quota limit?', __METHOD__, TL_ERROR);

			return;
		}

		// Adjust the file permissions (see #8178)
		$this->Files->chmod('system/tmp/' . $strTemp, 0666 & ~umask());

		// Then move the file to its final destination
		$this->Files->rename('system/tmp/' . $strTemp, 'system/config/localconfig.php');

		// Reset the Zend OPcache
		if (\function_exists('opcache_invalidate'))
		{
			opcache_invalidate($this->strRootDir . '/system/config/localconfig.php', true);
		}

		// Recompile the APC file (thanks to Trenker)
		if (\function_exists('apc_compile_file') && !ini_get('apc.stat'))
		{
			apc_compile_file($this->strRootDir . '/system/config/localconfig.php');
		}

		$this->blnIsModified = false;
	}

	/**
	 * Return true if the installation is complete
	 *
	 * @return boolean True if the installation is complete
	 */
	public static function isComplete()
	{
		return static::$blnHasLcf !== null && static::has('licenseAccepted');
	}

	/**
	 * Return all active modules as array
	 *
	 * @return array An array of active modules
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the container parameter "kernel.bundles" instead.
	 */
	public function getActiveModules()
	{
		@trigger_error('Using Config::getActiveModules() has been deprecated and will no longer work in Contao 5.0. Use the container parameter "kernel.bundles" instead.', E_USER_DEPRECATED);

		return ModuleLoader::getActive();
	}

	/**
	 * Add a configuration variable to the local configuration file
	 *
	 * @param string $strKey   The full variable name
	 * @param mixed  $varValue The configuration value
	 */
	public function add($strKey, $varValue)
	{
		$this->markModified();
		$this->arrData[$strKey] = $this->escape($varValue) . ';';
	}

	/**
	 * Alias for Config::add()
	 *
	 * @param string $strKey   The full variable name
	 * @param mixed  $varValue The configuration value
	 */
	public function update($strKey, $varValue)
	{
		$this->add($strKey, $varValue);
	}

	/**
	 * Remove a configuration variable
	 *
	 * @param string $strKey The full variable name
	 */
	public function delete($strKey)
	{
		$this->markModified();
		unset($this->arrData[$strKey]);
	}

	/**
	 * Check whether a configuration value exists
	 *
	 * @param string $strKey The short key
	 *
	 * @return boolean True if the configuration value exists
	 */
	public static function has($strKey)
	{
		return \array_key_exists($strKey, $GLOBALS['TL_CONFIG']);
	}

	/**
	 * Return a configuration value
	 *
	 * @param string $strKey The short key
	 *
	 * @return mixed|null The configuration value
	 */
	public static function get($strKey)
	{
		if (isset($GLOBALS['TL_CONFIG'][$strKey]))
		{
			return $GLOBALS['TL_CONFIG'][$strKey];
		}

		return null;
	}

	/**
	 * Temporarily set a configuration value
	 *
	 * @param string $strKey   The short key
	 * @param string $varValue The configuration value
	 */
	public static function set($strKey, $varValue)
	{
		$GLOBALS['TL_CONFIG'][$strKey] = $varValue;
	}

	/**
	 * Permanently set a configuration value
	 *
	 * @param string $strKey   The short key or full variable name
	 * @param mixed  $varValue The configuration value
	 */
	public static function persist($strKey, $varValue)
	{
		$objConfig = static::getInstance();

		if (strncmp($strKey, '$GLOBALS', 8) !== 0)
		{
			$strKey = "\$GLOBALS['TL_CONFIG']['$strKey']";
		}

		$objConfig->add($strKey, $varValue);
	}

	/**
	 * Permanently remove a configuration value
	 *
	 * @param string $strKey The short key or full variable name
	 */
	public static function remove($strKey)
	{
		$objConfig = static::getInstance();

		if (strncmp($strKey, '$GLOBALS', 8) !== 0)
		{
			$strKey = "\$GLOBALS['TL_CONFIG']['$strKey']";
		}

		$objConfig->delete($strKey);
	}

	/**
	 * Preload the default and local configuration
	 */
	public static function preload()
	{
		// Load the default files
		include __DIR__ . '/../../config/default.php';
		include __DIR__ . '/../../config/agents.php';
		include __DIR__ . '/../../config/mimetypes.php';

		$rootDir = System::getContainer()->getParameter('kernel.project_dir');

		// Include the local configuration file
		if (($blnHasLcf = file_exists($rootDir . '/system/config/localconfig.php')) === true)
		{
			include $rootDir . '/system/config/localconfig.php';
		}

		static::loadParameters();

		static::$blnHasLcf = $blnHasLcf;
	}

	/**
	 * Override the database and SMTP parameters
	 */
	protected static function loadParameters()
	{
		$container = System::getContainer();

		if ($container === null)
		{
			return;
		}

		if ($container->hasParameter('contao.localconfig') && \is_array($params = $container->getParameter('contao.localconfig')))
		{
			foreach ($params as $key=>$value)
			{
				$GLOBALS['TL_CONFIG'][$key] = $value;
			}
		}

		$arrMap = array
		(
			'dbHost'           => 'database_host',
			'dbPort'           => 'database_port',
			'dbUser'           => 'database_user',
			'dbPass'           => 'database_password',
			'dbDatabase'       => 'database_name',
			'smtpHost'         => 'mailer_host',
			'smtpUser'         => 'mailer_user',
			'smtpPass'         => 'mailer_password',
			'smtpPort'         => 'mailer_port',
			'smtpEnc'          => 'mailer_encryption',
			'addLanguageToUrl' => 'contao.prepend_locale',
			'encryptionKey'    => 'contao.encryption_key',
			'urlSuffix'        => 'contao.url_suffix',
			'uploadPath'       => 'contao.upload_path',
			'debugMode'        => 'kernel.debug',
		);

		foreach ($arrMap as $strKey=>$strParam)
		{
			if ($container->hasParameter($strParam))
			{
				$GLOBALS['TL_CONFIG'][$strKey] = $container->getParameter($strParam);
			}
		}

		if ($container->hasParameter('contao.image.valid_extensions'))
		{
			$GLOBALS['TL_CONFIG']['validImageTypes'] = implode(',', $container->getParameter('contao.image.valid_extensions'));
		}

		if ($container->hasParameter('contao.image.imagine_options'))
		{
			$GLOBALS['TL_CONFIG']['jpgQuality'] = $container->getParameter('contao.image.imagine_options')['jpeg_quality'];
		}
	}

	/**
	 * Escape a value depending on its type
	 *
	 * @param mixed $varValue The value
	 *
	 * @return mixed The escaped value
	 */
	protected function escape($varValue)
	{
		if (is_numeric($varValue) && !preg_match('/e|^[+-]?0[^.]/', $varValue) && $varValue < PHP_INT_MAX)
		{
			return $varValue;
		}

		if (\is_bool($varValue))
		{
			return $varValue ? 'true' : 'false';
		}

		if ($varValue == 'true')
		{
			return 'true';
		}

		if ($varValue == 'false')
		{
			return 'false';
		}

		return "'" . str_replace('\\"', '"', preg_replace('/[\n\r\t ]+/', ' ', addslashes($varValue))) . "'";
	}
}

class_alias(Config::class, 'Config');
