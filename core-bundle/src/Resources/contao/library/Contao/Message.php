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
		if (!$strMessage)
		{
			return;
		}

		if (!\in_array($strType, static::getTypes()))
		{
			throw new \Exception("Invalid message type $strType");
		}

		System::getContainer()->get('session')->getFlashBag()->add(static::getFlashBagKey($strType, $strScope), $strMessage);
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
		$strMessages = static::generateUnwrapped($strScope);

		if ($strMessages)
		{
			$strMessages = '<div class="tl_message">' . $strMessages . '</div>';
		}

		return $strMessages;
	}

	/**
	 * Return the messages as HTML
	 *
	 * @param string  $strScope An optional message scope
	 * @param boolean $blnRaw   Optionally return the raw messages
	 *
	 * @return string The messages HTML markup
	 */
	public static function generateUnwrapped($strScope=TL_MODE, $blnRaw=false)
	{
		$session = System::getContainer()->get('session');

		if (!$session->isStarted())
		{
			return '';
		}

		$strMessages = '';
		$flashBag = $session->getFlashBag();

		foreach (static::getTypes() as $strType)
		{
			$strClass = strtolower($strType);
			$arrMessages = $flashBag->get(static::getFlashBagKey($strType, $strScope));

			foreach (array_unique($arrMessages) as $strMessage)
			{
				if ($strType == 'TL_RAW' || $blnRaw)
				{
					$strMessages .= $strMessage;
				}
				else
				{
					$strMessages .= '<p class="' . $strClass . '">' . $strMessage . '</p>';
				}
			}
		}

		return trim($strMessages);
	}

	/**
	 * Reset the message system
	 */
	public static function reset()
	{
		$session = System::getContainer()->get('session');

		if (!$session->isStarted())
		{
			return;
		}

		$flashBag = $session->getFlashBag();

		// Find all contao. keys (see #3393)
		$keys = preg_grep('(^contao\.)', $flashBag->keys());

		foreach ($keys as $key)
		{
			$flashBag->get($key); // clears the message
		}
	}

	/**
	 * Return all available message types
	 *
	 * @return array An array of message types
	 */
	public static function getTypes()
	{
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
		$session = System::getContainer()->get('session');

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('error', $strScope));
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
		$session = System::getContainer()->get('session');

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('confirm', $strScope));
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
		$session = System::getContainer()->get('session');

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('new', $strScope));
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
		$session = System::getContainer()->get('session');

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('info', $strScope));
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
		$session = System::getContainer()->get('session');

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('raw', $strScope));
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
		return static::hasError($strScope) || static::hasConfirmation($strScope) || static::hasNew($strScope) || static::hasInfo($strScope) || static::hasRaw($strScope);
	}

	/**
	 * Return the flash bag key
	 *
	 * @param string      $strType  The message type
	 * @param string|null $strScope The message scope
	 *
	 * @return string The flash bag key
	 */
	protected static function getFlashBagKey($strType, $strScope=TL_MODE)
	{
		return 'contao.' . $strScope . '.' . strtolower(str_replace('TL_', '', $strType));
	}
}

class_alias(Message::class, 'Message');
