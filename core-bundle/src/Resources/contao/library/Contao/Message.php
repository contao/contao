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
	public static function addError($strMessage, $strScope=null)
	{
		static::add($strMessage, 'TL_ERROR', $strScope ?? self::getMode());
	}

	/**
	 * Add a confirmation message
	 *
	 * @param string $strMessage The confirmation message
	 * @param string $strScope   An optional message scope
	 */
	public static function addConfirmation($strMessage, $strScope=null)
	{
		static::add($strMessage, 'TL_CONFIRM', $strScope ?? self::getMode());
	}

	/**
	 * Add a new message
	 *
	 * @param string $strMessage The new message
	 * @param string $strScope   An optional message scope
	 */
	public static function addNew($strMessage, $strScope=null)
	{
		static::add($strMessage, 'TL_NEW', $strScope ?? self::getMode());
	}

	/**
	 * Add an info message
	 *
	 * @param string $strMessage The info message
	 * @param string $strScope   An optional message scope
	 */
	public static function addInfo($strMessage, $strScope=null)
	{
		static::add($strMessage, 'TL_INFO', $strScope ?? self::getMode());
	}

	/**
	 * Add a preformatted message
	 *
	 * @param string $strMessage The preformatted message
	 * @param string $strScope   An optional message scope
	 */
	public static function addRaw($strMessage, $strScope=null)
	{
		static::add($strMessage, 'TL_RAW', $strScope ?? self::getMode());
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
	public static function add($strMessage, $strType, $strScope=null)
	{
		if (!$strMessage)
		{
			return;
		}

		if (!\in_array($strType, static::getTypes()))
		{
			throw new \Exception("Invalid message type $strType");
		}

		System::getContainer()->get('request_stack')->getSession()->getFlashBag()->add(static::getFlashBagKey($strType, $strScope ?? self::getMode()), $strMessage);
	}

	/**
	 * Return the messages with a wrapping container as HTML
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return string The messages HTML markup
	 */
	public static function generate($strScope=null)
	{
		$strMessages = static::generateUnwrapped($strScope ?? self::getMode());

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
	public static function generateUnwrapped($strScope=null, $blnRaw=false)
	{
		$strScope ??= self::getMode();
		$session = System::getContainer()->get('request_stack')->getSession();

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
		$session = System::getContainer()->get('request_stack')->getSession();

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
	public static function hasError($strScope=null)
	{
		$session = System::getContainer()->get('request_stack')->getSession();

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('error', $strScope ?? self::getMode()));
	}

	/**
	 * Check if there are confirmation messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are confirmation messages
	 */
	public static function hasConfirmation($strScope=null)
	{
		$session = System::getContainer()->get('request_stack')->getSession();

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('confirm', $strScope ?? self::getMode()));
	}

	/**
	 * Check if there are new messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are new messages
	 */
	public static function hasNew($strScope=null)
	{
		$session = System::getContainer()->get('request_stack')->getSession();

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('new', $strScope ?? self::getMode()));
	}

	/**
	 * Check if there are info messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are info messages
	 */
	public static function hasInfo($strScope=null)
	{
		$session = System::getContainer()->get('request_stack')->getSession();

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('info', $strScope ?? self::getMode()));
	}

	/**
	 * Check if there are raw messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are raw messages
	 */
	public static function hasRaw($strScope=null)
	{
		$session = System::getContainer()->get('request_stack')->getSession();

		if (!$session->isStarted())
		{
			return false;
		}

		return $session->getFlashBag()->has(static::getFlashBagKey('raw', $strScope ?? self::getMode()));
	}

	/**
	 * Check if there are any messages
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return boolean True if there are messages
	 */
	public static function hasMessages($strScope=null)
	{
		$strScope ??= self::getMode();

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
	protected static function getFlashBagKey($strType, $strScope=null)
	{
		return 'contao.' . ($strScope ?? self::getMode()) . '.' . strtolower(str_replace('TL_', '', $strType));
	}

	private static function getMode(): string
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if (!$request)
		{
			return '';
		}

		$matcher = System::getContainer()->get('contao.routing.scope_matcher');

		if ($matcher->isBackendRequest($request))
		{
			return 'BE';
		}

		if ($matcher->isFrontendRequest($request))
		{
			return 'FE';
		}

		return '';
	}
}
