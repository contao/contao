<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;
use Symfony\Component\HttpFoundation\Session\Session;


/**
 * Stores and outputs messages
 *
 * The class handles system messages which are shown to the user. You can add
 * messages from anywhere in the application.
 *
 * Usage:
 *
 *     Message::addError('Please enter your name');
 *     Message::addConfirmation('The data has been stored');
 *     Message::addNew('There are two new messages');
 *     Message::addInfo('You can upload only two files');
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @deprecated Using \Contao\Message is deprecated. Use Symfony's flashbag messages instead.
 */
class Message
{

	/**
	 * Add an error message
	 *
	 * @param string $strMessage The error message
	 * @param string $strScope   An optional message scope
	 */
	public static function addError($strMessage, $strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		static::add($strMessage, 'TL_ERROR', $strScope);
	}


	/**
	 * Add a confirmation message
	 *
	 * @param string $strMessage The confirmation message
	 * @param string $strScope   An optional message scope
	 */
	public static function addConfirmation($strMessage, $strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		static::add($strMessage, 'TL_CONFIRM', $strScope);
	}


	/**
	 * Add a new message
	 *
	 * @param string $strMessage The new message
	 * @param string $strScope   An optional message scope
	 */
	public static function addNew($strMessage, $strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		static::add($strMessage, 'TL_NEW', $strScope);
	}


	/**
	 * Add an info message
	 *
	 * @param string $strMessage The info message
	 * @param string $strScope   An optional message scope
	 */
	public static function addInfo($strMessage, $strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		static::add($strMessage, 'TL_INFO', $strScope);
	}


	/**
	 * Add a preformatted message
	 *
	 * @param string $strMessage The preformatted message
	 * @param string $strScope   An optional message scope
	 */
	public static function addRaw($strMessage, $strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		static::add($strMessage, 'TL_RAW', $strScope);
	}


	/**
	 * Add a message
	 *
	 * @param string $strMessage The message text
	 * @param string $strType    The message type
	 * @param string $strScope   An optional message scope
	 *
	 * @throws \Exception If $strType is not a valid message type
	 */
	public static function add($strMessage, $strType, $strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		if ($strMessage == '')
		{
			return;
		}

		if (!in_array($strType, static::getTypes()))
		{
			throw new \Exception("Invalid message type $strType");
		}

		static::getSession()->getFlashBag()->add(static::getFlashBagKey(
			$strType,
			$strScope
		), $strMessage);
	}


	/**
	 * Return the messages with a wrapping container as HTML
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return string The messages HTML markup
	 */
	public static function generate($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		$strMessages = static::generateUnwrapped($strScope);

		if ($strMessages != '')
		{
			$strMessages = '<div class="tl_message">' . $strMessages . '</div>';
		}

		return $strMessages;
	}


	/**
	 * Return the messages as HTML
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return string The messages HTML markup
	 */
	public static function generateUnwrapped($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		$strMessages = '';

		foreach (static::getTypes() as $strType)
		{

			$strClass = strtolower($strType);

			$messages = static::getSession()->getFlashBag()->get(static::getFlashBagKey(
				$strType,
				$strScope
			));

			$messages = array_unique($messages);

			foreach ($messages as $strMessage)
			{
				if ($strType == 'TL_RAW')
				{
					$strMessages .= $strMessage;
				}
				else
				{
					$strMessages .= '<p class="' . $strClass . '">' . $strMessage . '</p>';
				}
			}

			if (!$_POST)
			{
				static::getSession()->getFlashBag()->set(static::getFlashBagKey(
					$strType,
					$strScope
				), null);
			}
		}

		return trim($strMessages);
	}


	/**
	 * Reset the message system
	 */
	public static function reset()
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		static::getSession()->getFlashBag()->clear();
	}


	/**
	 * Return all available message types
	 *
	 * @return array An array of message types
	 */
	public static function getTypes()
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		return array('TL_ERROR', 'TL_CONFIRM', 'TL_NEW', 'TL_INFO', 'TL_RAW');
	}


	/**
	 * Check if there are error messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are error messages
	 */
	public static function hasError($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		return static::getSession()->getFlashBag()->has(static::getFlashBagKey(
			'error',
			$strScope
		));
	}


	/**
	 * Check if there are confirmation messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are confirmation messages
	 */
	public static function hasConfirmation($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		return static::getSession()->getFlashBag()->has(static::getFlashBagKey(
			'confirm',
			$strScope
		));
	}


	/**
	 * Check if there are new messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are new messages
	 */
	public static function hasNew($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		return static::getSession()->getFlashBag()->has(static::getFlashBagKey(
			'new',
			$strScope
		));
	}


	/**
	 * Check if there are info messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are info messages
	 */
	public static function hasInfo($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		return static::getSession()->getFlashBag()->has(static::getFlashBagKey(
			'info',
			$strScope
		));
	}


	/**
	 * Check if there are raw messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are raw messages
	 */
	public static function hasRaw($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		return static::getSession()->getFlashBag()->has(static::getFlashBagKey(
			'raw',
			$strScope
		));
	}


	/**
	 * Check if there are any messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are messages
	 */
	public static function hasMessages($strScope=TL_MODE)
	{
		trigger_error('Using \Contao\Message is deprecated. Use Symfony\'s flashbag messages instead.', E_USER_DEPRECATED);

		return static::hasError($strScope) || static::hasConfirmation($strScope) || static::hasNew($strScope) || static::hasInfo($strScope) || static::hasRaw($strScope);
	}

	/**
	 * Gets the session.
	 *
	 * @return Session
	 */
	private static function getSession()
	{
		return \System::getContainer()->get('session');
	}

	/**
	 * Gets the flash bag key.
	 *
	 * @param             $type
	 * @param null|string $strScope
	 *
	 * @return string
	 */
	private function getFlashBagKey($type, $strScope = TL_MODE)
	{
		$scope = 'FE' === $strScope ? 'frontend' : 'backend';
		$type  = strtolower(str_replace('TL_', '', $type));

		return 'contao.' . $scope . '.' . $type;
	}
}
