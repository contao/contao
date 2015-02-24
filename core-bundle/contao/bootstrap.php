<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

global $kernel;

if (!defined('TL_MODE'))
{
	define('TL_MODE', 'FE');
}

define('TL_START', microtime(true));
define('TL_REFERER_ID', substr(md5(TL_START), 0, 8));
define('TL_ROOT', dirname($kernel->getRootDir()));

// Define the TL_SCRIPT constant (backwards compatibility)
if (!defined('TL_SCRIPT'))
{
    // TODO: TL_SCRIPT should be set here for legacy modules,
    // we currently use a listener which is too late.
//	define('TL_SCRIPT', null);
}

// Define the login status constants in the back end (see #4099, #5279)
if (TL_MODE == 'BE')
{
	define('BE_USER_LOGGED_IN', false);
	define('FE_USER_LOGGED_IN', false);
}

require TL_ROOT . '/system/helper/functions.php';
require TL_ROOT . '/system/config/constants.php';
require TL_ROOT . '/system/helper/interface.php';
require TL_ROOT . '/system/helper/exception.php';

// Try to disable the PHPSESSID
@ini_set('session.use_trans_sid', 0);
@ini_set('session.cookie_httponly', true);

// Set the error and exception handler
@set_error_handler('__error');
@set_exception_handler('__exception');

// Log PHP errors
@ini_set('error_log', TL_ROOT . '/system/logs/error.log');

// Include some classes required for further processing
require __DIR__ . '/library/Contao/Config.php';
class_alias('Contao\\Config', 'Config');

require __DIR__ . '/library/Contao/ClassLoader.php';
class_alias('Contao\\ClassLoader', 'ClassLoader');

require __DIR__ . '/library/Contao/TemplateLoader.php';
class_alias('Contao\\TemplateLoader', 'TemplateLoader');

require __DIR__ . '/library/Contao/ModuleLoader.php';
class_alias('Contao\\ModuleLoader', 'ModuleLoader');

// Preload the configuration (see #5872)
Config::preload();

// Try to load the modules
try
{
	ClassLoader::scanAndRegister();
}
catch (UnresolvableDependenciesException $e)
{
	die($e->getMessage()); // see #6343
}

// Override the SwiftMailer defaults
Swift::init(function() {
	$preferences = Swift_Preferences::getInstance();
	$preferences->setTempDir(TL_ROOT . '/system/tmp')->setCacheType('disk');
	$preferences->setCharset(Config::get('characterSet'));
});

// Define the relative path to the installation (see #5339)
if (Config::has('websitePath') && TL_SCRIPT != 'contao/install.php')
{
	Environment::set('path', Config::get('websitePath'));
}
elseif (TL_MODE == 'BE')
{
	Environment::set('path', preg_replace('/\/contao\/[a-z]+\.php$/i', '', Environment::get('scriptName')));
}

define('TL_PATH', Environment::get('path')); // backwards compatibility

// Start the session
@session_set_cookie_params(0, (Environment::get('path') ?: '/')); // see #5339
@session_start();

// Set the default language
if (!isset($_SESSION['TL_LANGUAGE']))
{
	$langs = Environment::get('httpAcceptLanguage');
	array_push($langs, 'en'); // see #6533

	foreach ($langs as $lang)
	{
		if (is_dir(__DIR__ . '/languages/' . str_replace('-', '_', $lang)))
		{
			$_SESSION['TL_LANGUAGE'] = $lang;
			break;
		}
	}

	unset($langs, $lang);
}

$GLOBALS['TL_LANGUAGE'] = $_SESSION['TL_LANGUAGE'];

// Fully load the configuration
$objConfig = Config::getInstance();

// Generate the symlinks before any potential output
if (!$objConfig->isComplete() && !is_link(TL_ROOT . '/system/themes/flexible'))
{
	$automator = new Automator();
	$automator->generateSymlinks();
}

// Show the "insecure document root" message
if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && substr(Environment::get('path'), -4) == '/web' && !Config::get('ignoreInsecureRoot'))
{
	die_nicely('be_insecure', 'Your installation is not secure. Please set the document root to the <code>/web</code> subfolder.');
}

// Show the "incomplete installation" message
if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && !$objConfig->isComplete())
{
	die_nicely('be_incomplete', 'The installation has not been completed. Open the Contao install tool to continue.');
}

Input::initialize();

// Always show error messages if logged into the install tool (see #5001)
if (Input::cookie('TL_INSTALL_AUTH') && !empty($_SESSION['TL_INSTALL_AUTH']) && Input::cookie('TL_INSTALL_AUTH') == $_SESSION['TL_INSTALL_AUTH'] && $_SESSION['TL_INSTALL_EXPIRE'] > time())
{
	Config::set('displayErrors', 1);
}

// Configure the error handling
@ini_set('display_errors', (Config::get('displayErrors') ? 1 : 0));
error_reporting((Config::get('displayErrors') || Config::get('logErrors')) ? Config::get('errorReporting') : 0);

// Set the timezone
@ini_set('date.timezone', Config::get('timeZone'));
@date_default_timezone_set(Config::get('timeZone'));

// Set the mbstring encoding
if (USE_MBSTRING && function_exists('mb_regex_encoding'))
{
	mb_regex_encoding(Config::get('characterSet'));
}

// HOOK: add custom logic (see #5665)
if (isset($GLOBALS['TL_HOOKS']['initializeSystem']) && is_array($GLOBALS['TL_HOOKS']['initializeSystem']))
{
	foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback)
	{
		System::importStatic($callback[0])->$callback[1]();
	}
}

// Include the custom initialization file
if (file_exists(TL_ROOT . '/system/config/initconfig.php'))
{
	include TL_ROOT . '/system/config/initconfig.php';
}

RequestToken::initialize();

// Check the request token upon POST requests
if ($_POST && !RequestToken::validate(Input::post('REQUEST_TOKEN')))
{
	// Force a JavaScript redirect upon Ajax requests (IE requires absolute link)
	if (Environment::get('isAjaxRequest'))
	{
		header('HTTP/1.1 204 No Content');
		header('X-Ajax-Location: ' . Environment::get('base') . 'contao/');
	}
	else
	{
		header('HTTP/1.1 400 Bad Request');
		die_nicely('be_referer', 'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.');
	}

	exit;
}
