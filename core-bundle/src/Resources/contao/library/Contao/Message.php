<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
		if ($strMessage == '')
		{
			return;
		}

		if (!in_array($strType, static::getTypes()))
		{
			throw new \Exception("Invalid message type $strType");
		}

		if (!is_array($_SESSION['MESSAGES'][$strScope][$strType]))
		{
			$_SESSION['MESSAGES'][$strScope][$strType] = array();
		}

		$_SESSION['MESSAGES'][$strScope][$strType][] = $strMessage;
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
		if (empty($_SESSION['MESSAGES'][$strScope]))
		{
			return '';
		}

		$strMessages = '';
		$arrMessages = &$_SESSION['MESSAGES'][$strScope];

		foreach (static::getTypes() as $strType)
		{
			if (!is_array($arrMessages[$strType]))
			{
				continue;
			}

			$strClass = strtolower($strType);
			$arrMessages[$strType] = array_unique($arrMessages[$strType]);

			foreach ($arrMessages[$strType] as $strMessage)
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
				$arrMessages[$strType] = array();
			}
		}

		return trim($strMessages);
	}


	/**
	 * Reset the message system
	 */
	public static function reset()
	{
		unset($_SESSION['MESSAGES']);
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
}
