<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as EmailMessage;

/**
 * A Mailer adapter class
 *
 * The class functions as an adapter for the Symfony mailer framework. It can be
 * used to send e-mails via the PHP mail function or an SMTP server.
 *
 * Usage:
 *
 *     $email = new Email();
 *     $email->subject = 'Hello';
 *     $email->text = 'Is it me you are looking for?';
 *     $email->sendTo('lionel@richie.com');
 *
 * @property string  $subject     The e-mail subject
 * @property string  $text        The text part of the mail
 * @property string  $html        The HTML part of the mail
 * @property string  $from        The sender's e-mail address
 * @property string  $fromName    The sender's name
 * @property string  $priority    The e-mail priority
 * @property string  $charset     The e-mail character set
 * @property string  $imageDir    The base directory to look for internal images
 * @property boolean $embedImages Whether to embed images inline
 * @property string  $logFile     The log file path
 * @property array   $failures    An array of rejected e-mail addresses
 *
 * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7;
 *             use the Symfony Mailer instead.
 */
class Email
{
	/**
	 * Mailer object
	 * @var MailerInterface
	 */
	protected $objMailer;

	/**
	 * Message object
	 * @var EmailMessage
	 */
	protected $objMessage;

	/**
	 * Sender e-mail address
	 * @var string
	 */
	protected $strSender;

	/**
	 * Sender name
	 * @var string
	 */
	protected $strSenderName;

	/**
	 * E-mail priority
	 * @var integer
	 */
	protected $intPriority;

	/**
	 * E-mail subject
	 * @var string
	 */
	protected $strSubject;

	/**
	 * Text part of the e-mail
	 * @var string
	 */
	protected $strText;

	/**
	 * HTML part of the e-mail
	 * @var string
	 */
	protected $strHtml;

	/**
	 * Character set
	 * @var string
	 */
	protected $strCharset;

	/**
	 * Image directory
	 * @var string
	 */
	protected $strImageDir;

	/**
	 * Embed images
	 * @var boolean
	 */
	protected $blnEmbedImages = true;

	/**
	 * Invalid addresses
	 * @var array
	 */
	protected $arrFailures = array();

	/**
	 * Log file name
	 * @var string
	 */
	protected $strLogFile = 'EMAIL';

	/**
	 * Instantiate the object and load the mailer framework
	 */
	public function __construct()
	{
		$this->objMailer = System::getContainer()->get('mailer');
		$this->strCharset = System::getContainer()->getParameter('kernel.charset');
		$this->objMessage = new EmailMessage();
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 *
	 * @throws \Exception If $strKey is unknown
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'subject':
				$this->strSubject = preg_replace(array('/[\t]+/', '/[\n\r]+/'), array(' ', ''), $varValue);
				break;

			case 'text':
				$this->strText = StringUtil::decodeEntities($varValue);
				break;

			case 'html':
				$this->strHtml = $varValue;
				break;

			case 'from':
				$this->strSender = $varValue;
				break;

			case 'fromName':
				$this->strSenderName = $varValue;
				break;

			case 'priority':
				switch ($varValue)
				{
					case 1:
					case 'highest':
						$this->intPriority = 1;
						break;

					case 2:
					case 'high':
						$this->intPriority = 2;
						break;

					case 3:
					case 'normal':
						$this->intPriority = 3;
						break;

					case 4:
					case 'low':
						$this->intPriority = 4;
						break;

					case 5:
					case 'lowest':
						$this->intPriority = 5;
						break;
				}
				break;

			case 'charset':
				$this->strCharset = $varValue;
				break;

			case 'imageDir':
				$this->strImageDir = $varValue;
				break;

			case 'embedImages':
				$this->blnEmbedImages = $varValue;
				break;

			case 'logFile':
				$this->strLogFile = $varValue;
				break;

			default:
				throw new \Exception(\sprintf('Invalid argument "%s"', $strKey));
		}
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed The property value
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'subject':
				return $this->strSubject;

			case 'text':
				return $this->strText;

			case 'html':
				return $this->strHtml;

			case 'from':
				return $this->strSender;

			case 'fromName':
				return $this->strSenderName;

			case 'priority':
				return $this->intPriority;

			case 'charset':
				return $this->strCharset;

			case 'imageDir':
				return $this->strImageDir;

			case 'embedImages':
				return $this->blnEmbedImages;

			case 'logFile':
				return $this->strLogFile;

			case 'failures':
				return $this->arrFailures;
		}

		return null;
	}

	/**
	 * Return true if there are failures
	 *
	 * @return boolean True if there are failures
	 */
	public function hasFailures()
	{
		return !empty($this->arrFailures);
	}

	/**
	 * Add a custom text header
	 *
	 * @param string $strKey   The header name
	 * @param string $strValue The header value
	 */
	public function addHeader($strKey, $strValue)
	{
		$this->objMessage->getHeaders()->addTextHeader($strKey, $strValue);
	}

	/**
	 * Add CC e-mail addresses
	 *
	 * Friendly name portions (e.g. Admin <admin@example.com>) are allowed. The
	 * method takes an unlimited number of recipient addresses.
	 */
	public function sendCc()
	{
		$this->objMessage->cc(...$this->compileRecipients(\func_get_args()));
	}

	/**
	 * Add BCC e-mail addresses
	 *
	 * Friendly name portions (e.g. Admin <admin@example.com>) are allowed. The
	 * method takes an unlimited number of recipient addresses.
	 */
	public function sendBcc()
	{
		$this->objMessage->bcc(...$this->compileRecipients(\func_get_args()));
	}

	/**
	 * Add ReplyTo e-mail addresses
	 *
	 * Friendly name portions (e.g. Admin <admin@example.com>) are allowed. The
	 * method takes an unlimited number of recipient addresses.
	 */
	public function replyTo()
	{
		$this->objMessage->replyTo(...$this->compileRecipients(\func_get_args()));
	}

	/**
	 * Attach a file
	 *
	 * @param string $strFile The file path
	 * @param string $strMime The MIME type (defaults to "application/octet-stream")
	 */
	public function attachFile($strFile, $strMime='application/octet-stream')
	{
		$this->objMessage->attachFromPath($strFile, basename($strFile), $strMime);
	}

	/**
	 * Attach a file from a string
	 *
	 * @param string $strContent  The file content
	 * @param string $strFilename The file name
	 * @param string $strMime     The MIME type (defaults to "application/octet-stream")
	 */
	public function attachFileFromString($strContent, $strFilename, $strMime='application/octet-stream')
	{
		$this->objMessage->attach($strContent, $strFilename, $strMime);
	}

	/**
	 * Send the e-mail
	 *
	 * Friendly name portions (e.g. Admin <admin@example.com>) are allowed. The
	 * method takes an unlimited number of recipient addresses.
	 *
	 * @return boolean True if the e-mail was sent successfully
	 */
	public function sendTo()
	{
		$arrRecipients = $this->compileRecipients(\func_get_args());

		if (empty($arrRecipients))
		{
			return false;
		}

		$this->objMessage->to(...$arrRecipients);

		// Add the priority if it has been set (see #608)
		if ($this->intPriority !== null)
		{
			$this->objMessage->priority($this->intPriority);
		}

		// Default subject
		if (!$this->strSubject)
		{
			$this->strSubject = 'No subject';
		}

		$this->objMessage->subject($this->strSubject);

		// HTML e-mail
		if ($this->strHtml)
		{
			// Embed images
			if ($this->blnEmbedImages)
			{
				if (!$this->strImageDir)
				{
					$this->strImageDir = System::getContainer()->getParameter('kernel.project_dir') . '/';
				}

				$arrCid = array();
				$arrMatches = array();
				$strBase = Environment::get('base');

				// Thanks to @ofriedrich and @aschempp (see #4562)
				preg_match_all('/<[a-z][a-z0-9]*\b[^>]*((src=|background=|url\()["\']??)(.+\.(jpe?g|png|gif|bmp|tiff?|swf|svg))(["\' ]??(\)??))[^>]*>/Ui', $this->strHtml, $arrMatches);

				// Check for internal images
				if (!empty($arrMatches) && isset($arrMatches[0]))
				{
					for ($i=0, $c=\count($arrMatches[0]); $i<$c; $i++)
					{
						$url = $arrMatches[3][$i];

						// Try to remove the base URL
						$src = str_replace($strBase, '', $url);
						$src = rawurldecode($src); // see #3713

						// Embed the image if the URL is now relative
						if (!preg_match('@^https?://@', $src) && ($objFile = new File(StringUtil::stripRootDir($this->strImageDir . $src))) && ($objFile->exists() || $objFile->createIfDeferred()))
						{
							if (!isset($arrCid[$src]))
							{
								// See https://symfony.com/doc/current/mailer.html#embedding-images
								$this->objMessage->embedFromPath($this->strImageDir . $src, $src);
								$arrCid[$src] = 'cid:' . $src;
							}

							$this->strHtml = str_replace($arrMatches[1][$i] . $arrMatches[3][$i] . $arrMatches[5][$i], $arrMatches[1][$i] . $arrCid[$src] . $arrMatches[5][$i], $this->strHtml);
						}
					}
				}
			}

			$this->objMessage->html($this->strHtml, $this->strCharset);
		}

		// Text content
		if ($this->strText)
		{
			$this->objMessage->text($this->strText, $this->strCharset);
		}

		// Add the administrator e-mail as default sender
		if (!$this->strSender)
		{
			if (!empty($GLOBALS['TL_ADMIN_EMAIL']))
			{
				$this->strSender = $GLOBALS['TL_ADMIN_EMAIL'];
				$this->strSenderName = $GLOBALS['TL_ADMIN_NAME'] ?? null;
			}
			elseif ($adminEmail = Config::get('adminEmail'))
			{
				list($this->strSenderName, $this->strSender) = StringUtil::splitFriendlyEmail($adminEmail);
			}
			else
			{
				throw new \Exception('No administrator e-mail address has been set.');
			}
		}

		// Sender
		$this->objMessage->from(new Address($this->strSender, $this->strSenderName ?? ''));

		// Set the return path (see #5004)
		$this->objMessage->returnPath($this->strSender);

		// Send the e-mail
		$this->objMailer->send($this->objMessage);

		return true;
	}

	/**
	 * Extract the e-mail addresses from the func_get_args() arguments
	 *
	 * @param array $arrRecipients The recipients array
	 *
	 * @return array An array of e-mail addresses
	 */
	protected function compileRecipients($arrRecipients)
	{
		$arrReturn = array();

		foreach ($arrRecipients as $varRecipients)
		{
			if (!\is_array($varRecipients))
			{
				$varRecipients = StringUtil::splitCsv($varRecipients);
			}

			// Support friendly name addresses and internationalized domain names
			foreach ($varRecipients as $v)
			{
				list($strName, $strEmail) = StringUtil::splitFriendlyEmail($v);

				$strName = trim($strName, ' "');
				$strEmail = Idna::encodeEmail($strEmail);

				if ($this->objMessage instanceof EmailMessage)
				{
					$arrReturn[] = new Address($strEmail, $strName);
				}
				elseif ($strName)
				{
					$arrReturn[$strEmail] = $strName;
				}
				else
				{
					$arrReturn[] = $strEmail;
				}
			}
		}

		return $arrReturn;
	}
}
