<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


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
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0. Use
 *             the Symfony Session via the container instead.
 */
class Session
{

	/**
	 * Object instance (Singleton)
	 * @var \Session
	 */
	protected static $objInstance;

	/**
	 * Symfony session object
	 * @var SessionInterface
	 */
	private $session;


	/**
	 * Get the session data
	 */
	protected function __construct()
	{
		/** @var KernelInterface $kernel */
		global $kernel;

		$this->session = $kernel->getContainer()->get('session');
	}


	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone() {}


	/**
	 * Return the object instance (Singleton)
	 *
	 * @return \Session The object instance
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
		/** @var AttributeBagInterface $bag */
		$bag = $this->session->getBag($this->getSessionBagKey());

		return $bag->get($strKey);
	}


	/**
	 * Set a session variable
	 *
	 * @param string $strKey   The variable name
	 * @param mixed  $varValue The variable value
	 */
	public function set($strKey, $varValue)
	{
		/** @var AttributeBagInterface $bag */
		$bag = $this->session->getBag($this->getSessionBagKey());

		$bag->set($strKey, $varValue);
	}


	/**
	 * Remove a session variable
	 *
	 * @param string $strKey The variable name
	 */
	public function remove($strKey)
	{
		/** @var AttributeBagInterface $bag */
		$bag = $this->session->getBag($this->getSessionBagKey());

		$bag->remove($strKey);
	}


	/**
	 * Return the session data as array
	 *
	 * @return array The session data
	 */
	public function getData()
	{
		/** @var AttributeBagInterface $bag */
		$bag = $this->session->getBag($this->getSessionBagKey());

		return $bag->all();
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
		if (!is_array($arrData))
		{
			throw new \Exception('Array required to set session data');
		}

		/** @var AttributeBagInterface $bag */
		$bag = $this->session->getBag($this->getSessionBagKey());

		$bag->replace($arrData);
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
		if (is_object($varData))
		{
			$varData = get_object_vars($varData);
		}

		if (!is_array($varData))
		{
			throw new \Exception('Array or object required to append session data');
		}

		/** @var AttributeBagInterface $bag */
		$bag = $this->session->getBag($this->getSessionBagKey());

		foreach ($varData as $k=>$v)
		{
			$bag->set($k, $v);
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
