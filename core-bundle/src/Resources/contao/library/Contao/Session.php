<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

trigger_deprecation('contao/core-bundle', '4.0', 'Using the "Contao\Session" class has been deprecated and will no longer work in Contao 5.0. Use the session service instead.');

/**
 * Handles reading and updating the session data
 *
 * The class functions as an adapter for the PHP $_SESSION array and separates
 * back end from front end session data.
 *
 * Usage:
 *
 *     $session = Session::getInstance();
 *     $session->set('foo', 'bar');
 *     echo $session->get('foo');
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use the session service instead.
 */
class Session
{
	/**
	 * Object instance (Singleton)
	 * @var Session
	 */
	protected static $objInstance;

	/**
	 * Symfony session object
	 * @var SymfonySession
	 */
	private $session;

	/**
	 * Symfony session bag
	 * @var AttributeBagInterface
	 */
	private $sessionBag;

	/**
	 * Session keys that are not stored in the parameter bag
	 * @var array
	 */
	private static $mappedKeys = array('referer', 'popupReferer', 'CURRENT_ID');

	/**
	 * Get the session data
	 */
	protected function __construct()
	{
		if (\PHP_SAPI == 'cli')
		{
			$this->session = new SymfonySession(new MockArraySessionStorage());
		}
		else
		{
			$this->session = System::getContainer()->get('session');
		}

		$this->sessionBag = $this->session->getBag($this->getSessionBagKey());
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone()
	{
	}

	/**
	 * Return the object instance (Singleton)
	 *
	 * @return Session The object instance
	 */
	public static function getInstance()
	{
		if (static::$objInstance === null)
		{
			static::$objInstance = new static();
		}

		return static::$objInstance;
	}

	/**
	 * Return a session variable
	 *
	 * @param string $strKey The variable name
	 *
	 * @return mixed The variable value
	 */
	public function get($strKey)
	{
		// Map the referer (see #281)
		if (\in_array($strKey, self::$mappedKeys))
		{
			return $this->session->get($strKey);
		}

		return $this->sessionBag->get($strKey);
	}

	/**
	 * Set a session variable
	 *
	 * @param string $strKey   The variable name
	 * @param mixed  $varValue The variable value
	 */
	public function set($strKey, $varValue)
	{
		// Map the referer (see #281)
		if (\in_array($strKey, self::$mappedKeys))
		{
			$this->session->set($strKey, $varValue);
		}
		else
		{
			$this->sessionBag->set($strKey, $varValue);
		}
	}

	/**
	 * Remove a session variable
	 *
	 * @param string $strKey The variable name
	 */
	public function remove($strKey)
	{
		// Map the referer (see #281)
		if (\in_array($strKey, self::$mappedKeys))
		{
			$this->session->remove($strKey);
		}
		else
		{
			$this->sessionBag->remove($strKey);
		}
	}

	/**
	 * Return the session data as array
	 *
	 * @return array The session data
	 */
	public function getData()
	{
		$data = $this->sessionBag->all();

		// Map the referer (see #281)
		foreach (self::$mappedKeys as $strKey)
		{
			unset($data[$strKey]);

			if ($this->session->has($strKey))
			{
				$data[$strKey] = $this->session->get($strKey);
			}
		}

		return $data;
	}

	/**
	 * Set the session data from an array
	 *
	 * @param array $arrData The session data
	 *
	 * @throws \Exception If $arrData is not an array
	 */
	public function setData($arrData)
	{
		if (!\is_array($arrData))
		{
			throw new \Exception('Array required to set session data');
		}

		// Map the referer (see #281)
		foreach (self::$mappedKeys as $strKey)
		{
			if (isset($arrData[$strKey]))
			{
				$this->session->set($strKey, $arrData[$strKey]);
				unset($arrData[$strKey]);
			}
		}

		$this->sessionBag->replace($arrData);
	}

	/**
	 * Append data to the session
	 *
	 * @param mixed $varData The data object or array
	 *
	 * @throws \Exception If $varData is not an array or object
	 */
	public function appendData($varData)
	{
		if (\is_object($varData))
		{
			$varData = get_object_vars($varData);
		}

		if (!\is_array($varData))
		{
			throw new \Exception('Array or object required to append session data');
		}

		foreach ($varData as $k=>$v)
		{
			// Map the referer (see #281)
			if (\in_array($k, self::$mappedKeys))
			{
				$this->session->set($k, $v);
			}
			else
			{
				$this->sessionBag->set($k, $v);
			}
		}
	}

	/**
	 * Gets the correct session bag key depending on the Contao environment
	 *
	 * @return string
	 */
	private function getSessionBagKey()
	{
		switch (TL_MODE)
		{
			case 'BE':
				return 'contao_backend';

			case 'FE':
				return 'contao_frontend';

			default:
				return 'attributes';
		}
	}
}

class_alias(Session::class, 'Session');
