<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

trigger_deprecation('contao/core-bundle', '3.5', 'Using the "Contao\Encryption" class has been deprecated and will no longer work in Contao 5.0. Use the PHP password_* functions and a third-party library such as OpenSSL or phpseclib instead.');

/**
 * Encrypts and decrypts data
 *
 * The class can be used to encrypt and decrypt data based on the encryption
 * string that is set during the Contao installation.
 *
 * Usage:
 *
 *     $encrypted = Encryption::encrypt('Leo Feyer');
 *     $decrypted = Encryption::decrypt($encrypted);
 *
 * @deprecated Deprecated since Contao 3.5, to be removed in Contao 5.0.
 *             Use the PHP password_* functions and a third-party library such as OpenSSL or phpseclib instead.
 */
class Encryption
{
	/**
	 * Object instance (Singleton)
	 * @var Encryption
	 */
	protected static $objInstance;

	/**
	 * Mcrypt resource
	 * @var resource
	 */
	protected static $resTd;

	/**
	 * @deprecated Use a third-party library such as OpenSSL or phpseclib instead
	 */
	public static function encrypt($varValue, $strKey=null)
	{
		throw new \BadMethodCallException('This method is not supported anymore, because the PHP mcrypt extension has been removed in PHP 7.2.');
	}

	/**
	 * @deprecated Use a third-party library such as OpenSSL or phpseclib instead
	 */
	public static function decrypt($varValue, $strKey=null)
	{
		throw new \BadMethodCallException('This method is not supported anymore, because the PHP mcrypt extension has been removed in PHP 7.2.');
	}

	/**
	 * Generate a password hash
	 *
	 * @param string $strPassword The unencrypted password
	 *
	 * @return string The encrypted password
	 */
	public static function hash($strPassword)
	{
		$passwordHasher = System::getContainer()->get('security.password_hasher_factory')->getPasswordHasher(User::class);

		return $passwordHasher->hash($strPassword);
	}

	/**
	 * Test whether a password hash has been generated with crypt()
	 *
	 * @param string $strHash The password hash
	 *
	 * @return boolean True if the password hash has been generated with crypt()
	 */
	public static function test($strHash)
	{
		if (strncmp($strHash, '$2y$', 4) === 0)
		{
			return true;
		}

		if (strncmp($strHash, '$2a$', 4) === 0)
		{
			return true;
		}

		if (strncmp($strHash, '$6$', 3) === 0)
		{
			return true;
		}

		if (strncmp($strHash, '$5$', 3) === 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Verify a readable password against a password hash
	 *
	 * @param string $strPassword The readable password
	 * @param string $strHash     The password hash
	 *
	 * @return boolean True if the password could be verified
	 *
	 * @see https://github.com/ircmaxell/password_compat
	 */
	public static function verify($strPassword, $strHash)
	{
		$passwordHasher = System::getContainer()->get('security.password_hasher_factory')->getPasswordHasher(User::class);

		return $passwordHasher->verify($strHash, $strPassword);
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
	 * @return Encryption
	 */
	public static function getInstance()
	{
		if (static::$objInstance === null)
		{
			static::$objInstance = new static();
		}

		return static::$objInstance;
	}
}

class_alias(Encryption::class, 'Encryption');
