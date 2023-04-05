<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database\Result;
use Contao\NewsletterBundle\Event\SendNewsletterEvent;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * Provide methods to handle newsletters.
 */
class Newsletter extends Backend
{
	/**
	 * Return a form to choose an existing CSV file and import it
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function send(DataContainer $dc)
	{
		$objNewsletter = $this->Database->prepare("SELECT n.*, c.template AS channelTemplate, c.sender AS channelSender, c.senderName as channelSenderName, c.mailerTransport AS channelMailerTransport FROM tl_newsletter n LEFT JOIN tl_newsletter_channel c ON n.pid=c.id WHERE n.id=?")
										->limit(1)
										->execute($dc->id);

		// Return if there is no newsletter
		if ($objNewsletter->numRows < 1)
		{
			return '';
		}

		System::loadLanguageFile('tl_newsletter_channel');

		// Set the template
		if (!$objNewsletter->template)
		{
			$objNewsletter->template = $objNewsletter->channelTemplate;
		}

		// Set the sender address
		if (!$objNewsletter->sender)
		{
			$objNewsletter->sender = $objNewsletter->channelSender;
		}

		// Set the sender name
		if (!$objNewsletter->senderName)
		{
			$objNewsletter->senderName = $objNewsletter->channelSenderName;
		}

		// No sender address given
		if (!$objNewsletter->sender)
		{
			throw new InternalServerErrorException('No sender address given. Please check the newsletter channel settings.');
		}

		$arrAttachments = array();

		// Add attachments
		if ($objNewsletter->addFile)
		{
			$files = StringUtil::deserialize($objNewsletter->files);

			if (!empty($files) && \is_array($files))
			{
				$objFiles = FilesModel::findMultipleByUuids($files);

				if ($objFiles !== null)
				{
					$projectDir = System::getContainer()->getParameter('kernel.project_dir');

					while ($objFiles->next())
					{
						if (is_file($projectDir . '/' . $objFiles->path))
						{
							$arrAttachments[] = $objFiles->path;
						}
					}
				}
			}
		}

		// Replace insert tags
		$html = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objNewsletter->content ?? '');
		$text = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objNewsletter->text ?? '');

		// Convert relative URLs
		if ($objNewsletter->externalImages)
		{
			$html = $this->convertRelativeUrls($html);
		}

		$objSession = System::getContainer()->get('session');
		$token = Input::get('token');

		// Send newsletter
		if ($token && $token == $objSession->get('tl_newsletter_send'))
		{
			$referer = preg_replace('/&(amp;)?(start|mpc|token|recipient|preview)=[^&]*/', '', Environment::get('request'));

			// Preview
			if (isset($_GET['preview']))
			{
				// Check the e-mail address
				if (!Validator::isEmail(Input::get('recipient', true)))
				{
					$_SESSION['TL_PREVIEW_MAIL_ERROR'] = true;
					$this->redirect($referer);
				}

				$arrRecipient['email'] = urldecode(Input::get('recipient', true));

				// Send
				$objEmail = $this->generateEmailObject($objNewsletter, $arrAttachments);

				if ($this->sendNewsletter($objEmail, $objNewsletter, $arrRecipient, $text, $html))
				{
					Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_newsletter']['confirm'], 1));
				}

				$this->redirect($referer);
			}

			// Get the total number of recipients
			$objTotal = $this->Database->prepare("SELECT COUNT(DISTINCT email) AS count FROM tl_newsletter_recipients WHERE pid=? AND active=1")
									   ->execute($objNewsletter->pid);

			// Return if there are no recipients
			if ($objTotal->count < 1)
			{
				$objSession->set('tl_newsletter_send', null);
				Message::addError($GLOBALS['TL_LANG']['tl_newsletter']['error']);
				$this->redirect($referer);
			}

			$intTotal = $objTotal->count;

			// Get page and timeout
			$intTimeout = (Input::get('timeout') > 0) ? Input::get('timeout') : 1;
			$intStart = Input::get('start') ?: 0;
			$intPages = Input::get('mpc') ?: 10;

			// Get recipients
			$objRecipients = $this->Database->prepare("SELECT *, r.email FROM tl_newsletter_recipients r LEFT JOIN tl_member m ON(r.email=m.email) WHERE r.pid=? AND r.active=1 ORDER BY r.email")
											->limit($intPages, $intStart)
											->execute($objNewsletter->pid);

			echo '<div style="font-family:Verdana,sans-serif;font-size:11px;line-height:16px;margin-bottom:12px">';

			// Send newsletter
			if ($objRecipients->numRows > 0)
			{
				// Update status
				if ($intStart == 0)
				{
					$this->Database->prepare("UPDATE tl_newsletter SET sent='1', date=? WHERE id=?")
								   ->execute(time(), $objNewsletter->id);

					$_SESSION['REJECTED_RECIPIENTS'] = array();
					$_SESSION['SKIPPED_RECIPIENTS'] = array();
				}

				$time = time();

				while ($objRecipients->next())
				{
					// Skip the recipient if the member is not active (see #8812)
					if ($objRecipients->id !== null && ($objRecipients->disable || ($objRecipients->start && $objRecipients->start > $time) || ($objRecipients->stop && $objRecipients->stop <= $time)))
					{
						--$intTotal;
						echo 'Skipping <strong>' . Idna::decodeEmail($objRecipients->email) . '</strong> as the member is not active<br>';
						continue;
					}

					$objEmail = $this->generateEmailObject($objNewsletter, $arrAttachments);

					if ($this->sendNewsletter($objEmail, $objNewsletter, $objRecipients->row(), $text, $html))
					{
						echo 'Sending newsletter to <strong>' . Idna::decodeEmail($objRecipients->email) . '</strong><br>';
					}
					else
					{
						$_SESSION['SKIPPED_RECIPIENTS'][] = $objRecipients->email;
						echo 'Skipping <strong>' . Idna::decodeEmail($objRecipients->email) . '</strong><br>';
					}
				}
			}

			echo '<div style="margin-top:12px">';

			// Redirect back home
			if ($objRecipients->numRows < 1 || ($intStart + $intPages) >= $intTotal)
			{
				$objSession->set('tl_newsletter_send', null);

				// Deactivate rejected addresses
				if (!empty($_SESSION['REJECTED_RECIPIENTS']))
				{
					$intRejected = \count($_SESSION['REJECTED_RECIPIENTS']);
					Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_newsletter']['rejected'], $intRejected));
					$intTotal -= $intRejected;

					foreach ($_SESSION['REJECTED_RECIPIENTS'] as $strRecipient)
					{
						$this->Database->prepare("UPDATE tl_newsletter_recipients SET active='' WHERE email=?")
									   ->execute($strRecipient);

						System::getContainer()->get('monolog.logger.contao.error')->error('Recipient address "' . Idna::decodeEmail($strRecipient) . '" was rejected and has been deactivated');
					}
				}

				if ($intSkipped = \count($_SESSION['SKIPPED_RECIPIENTS']))
				{
					$intTotal -= $intSkipped;
					Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_newsletter']['skipped'], $intSkipped));
				}

				unset($_SESSION['REJECTED_RECIPIENTS'], $_SESSION['SKIPPED_RECIPIENTS']);

				Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_newsletter']['confirm'], $intTotal));

				echo '<script>setTimeout(\'window.location="' . Environment::get('base') . $referer . '"\',1000)</script>';
				echo '<a href="' . Environment::get('base') . $referer . '">Please click here to proceed if you are not using JavaScript</a>';
			}

			// Redirect to the next cycle
			else
			{
				$url = preg_replace('/&(amp;)?(start|mpc|recipient)=[^&]*/', '', Environment::get('request')) . '&start=' . ($intStart + $intPages) . '&mpc=' . $intPages;

				echo '<script>setTimeout(\'window.location="' . Environment::get('base') . $url . '"\',' . ($intTimeout * 1000) . ')</script>';
				echo '<a href="' . Environment::get('base') . $url . '">Please click here to proceed if you are not using JavaScript</a>';
			}

			echo '</div></div>';
			exit;
		}

		$strToken = md5(uniqid(mt_rand(), true));
		$objSession->set('tl_newsletter_send', $strToken);
		$sprintf = $objNewsletter->senderName ? $objNewsletter->senderName . ' &lt;%s&gt;' : '%s';
		$this->import(BackendUser::class, 'User');

		// Preview newsletter
		$return = Message::generate() . '
<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
<form action="' . TL_SCRIPT . '" id="tl_newsletter_send" class="tl_form tl_edit_form" method="get">
<div class="tl_formbody_edit tl_newsletter_send">
<input type="hidden" name="do" value="' . Input::get('do') . '">
<input type="hidden" name="table" value="' . Input::get('table') . '">
<input type="hidden" name="key" value="' . Input::get('key') . '">
<input type="hidden" name="id" value="' . Input::get('id') . '">
<input type="hidden" name="token" value="' . $strToken . '">
<table class="prev_header">
  <tr class="row_0">
    <td class="col_0">' . $GLOBALS['TL_LANG']['tl_newsletter']['from'] . '</td>
    <td class="col_1">' . sprintf($sprintf, Idna::decodeEmail($objNewsletter->sender)) . '</td>
  </tr>
  <tr class="row_1">
    <td class="col_0">' . $GLOBALS['TL_LANG']['tl_newsletter']['subject'][0] . '</td>
    <td class="col_1">' . $objNewsletter->subject . '</td>
  </tr>
  <tr class="row_2">
    <td class="col_0">' . $GLOBALS['TL_LANG']['tl_newsletter_channel']['template'][0] . '</td>
    <td class="col_1">' . ($objNewsletter->template ?: 'mail_default') . '</td>
  </tr>' . ((!empty($arrAttachments) && \is_array($arrAttachments)) ? '
  <tr class="row_3">
    <td class="col_0">' . $GLOBALS['TL_LANG']['tl_newsletter']['attachments'] . '</td>
    <td class="col_1">' . implode(', ', $arrAttachments) . '</td>
  </tr>' : '') . '
</table>' . (!$objNewsletter->sendText ? '
<div class="preview_html">
' . $html . '
</div>' : '') . '
<div class="preview_text">
<pre style="white-space:pre-wrap">' . $text . '</pre>
</div>

<fieldset class="tl_tbox nolegend">
<div class="w50 widget">
  <h3><label for="ctrl_mpc">' . $GLOBALS['TL_LANG']['tl_newsletter']['mailsPerCycle'][0] . '</label></h3>
  <input type="text" name="mpc" id="ctrl_mpc" value="10" class="tl_text" onfocus="Backend.getScrollOffset()">' . (($GLOBALS['TL_LANG']['tl_newsletter']['mailsPerCycle'][1] && Config::get('showHelp')) ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_newsletter']['mailsPerCycle'][1] . '</p>' : '') . '
</div>
<div class="w50 widget">
  <h3><label for="ctrl_timeout">' . $GLOBALS['TL_LANG']['tl_newsletter']['timeout'][0] . '</label></h3>
  <input type="text" name="timeout" id="ctrl_timeout" value="1" class="tl_text" onfocus="Backend.getScrollOffset()">' . (($GLOBALS['TL_LANG']['tl_newsletter']['timeout'][1] && Config::get('showHelp')) ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_newsletter']['timeout'][1] . '</p>' : '') . '
</div>
<div class="w50 widget">
  <h3><label for="ctrl_start">' . $GLOBALS['TL_LANG']['tl_newsletter']['start'][0] . '</label></h3>
  <input type="text" name="start" id="ctrl_start" value="0" class="tl_text" onfocus="Backend.getScrollOffset()">' . (($GLOBALS['TL_LANG']['tl_newsletter']['start'][1] && Config::get('showHelp')) ? '
  <p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_newsletter']['start'][1], $objNewsletter->id) . '</p>' : '') . '
</div>
<div class="w50 widget">
  <h3><label for="ctrl_recipient">' . $GLOBALS['TL_LANG']['tl_newsletter']['sendPreviewTo'][0] . '</label></h3>
  <input type="text" name="recipient" id="ctrl_recipient" value="' . Idna::decodeEmail($this->User->email) . '" class="tl_text" onfocus="Backend.getScrollOffset()">' . (isset($_SESSION['TL_PREVIEW_MAIL_ERROR']) ? '
  <div class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['email'] . '</div>' : (($GLOBALS['TL_LANG']['tl_newsletter']['sendPreviewTo'][1] && Config::get('showHelp')) ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_newsletter']['sendPreviewTo'][1] . '</p>' : '')) . '
</div>
</fieldset>
</div>';

		$return .= '

<div class="tl_formbody_submit">
<div class="tl_submit_container">
<button type="submit" name="preview" class="tl_submit" accesskey="p">' . $GLOBALS['TL_LANG']['tl_newsletter']['preview'] . '</button>
<button type="submit" id="send" class="tl_submit" accesskey="s" onclick="return confirm(\'' . str_replace("'", "\\'", $GLOBALS['TL_LANG']['tl_newsletter']['sendConfirm']) . '\')">' . $GLOBALS['TL_LANG']['tl_newsletter']['send'][0] . '</button>
</div>
</div>

</form>';

		unset($_SESSION['TL_PREVIEW_MAIL_ERROR']);

		return $return;
	}

	/**
	 * Generate the e-mail object and return it
	 *
	 * @param Result $objNewsletter
	 * @param array  $arrAttachments
	 *
	 * @return Email
	 */
	protected function generateEmailObject(Result $objNewsletter, $arrAttachments)
	{
		$objEmail = new Email();
		$objEmail->from = $objNewsletter->sender;
		$objEmail->subject = $objNewsletter->subject;

		// Add sender name
		if ($objNewsletter->senderName)
		{
			$objEmail->fromName = $objNewsletter->senderName;
		}

		$objEmail->embedImages = !$objNewsletter->externalImages;
		$objEmail->logFile = ContaoContext::NEWSLETTER . '_' . $objNewsletter->id;

		// Attachments
		if (!empty($arrAttachments) && \is_array($arrAttachments))
		{
			$projectDir = System::getContainer()->getParameter('kernel.project_dir');

			foreach ($arrAttachments as $strAttachment)
			{
				$objEmail->attachFile($projectDir . '/' . $strAttachment);
			}
		}

		// Add transport
		if (!empty($objNewsletter->mailerTransport) || !empty($objNewsletter->channelMailerTransport))
		{
			$objEmail->addHeader('X-Transport', $objNewsletter->mailerTransport ?: $objNewsletter->channelMailerTransport);
		}

		// Newsletters with an unsubscribe header are less likely to be blocked (see #2174)
		$objEmail->addHeader('List-Unsubscribe', '<mailto:' . $objNewsletter->sender . '?subject=Unsubscribe%20channel%20' . $objNewsletter->pid . '>');

		return $objEmail;
	}

	/**
	 * Compile the newsletter and send it
	 *
	 * @param Email  $objEmail
	 * @param Result $objNewsletter
	 * @param array  $arrRecipient
	 * @param string $text
	 * @param string $html
	 * @param string $css
	 *
	 * @return boolean
	 */
	protected function sendNewsletter(Email $objEmail, Result $objNewsletter, $arrRecipient, $text, $html, $css=null)
	{
		$simpleTokenParser = System::getContainer()->get('contao.string.simple_token_parser');

		// Prepare the text content
		$objEmail->text = $simpleTokenParser->parse($text, $arrRecipient);

		if (!$objNewsletter->sendText)
		{
			$objTemplate = new BackendTemplate($objNewsletter->template ?: 'mail_default');
			$objTemplate->setData($objNewsletter->row());
			$objTemplate->title = $objNewsletter->subject;
			$objTemplate->body = $simpleTokenParser->parse($html, $arrRecipient);
			$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');
			$objTemplate->recipient = $arrRecipient['email'];

			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			$objTemplate->css = $css;

			// Parse template
			$objEmail->html = $objTemplate->parse();
			$objEmail->imageDir = System::getContainer()->getParameter('kernel.project_dir') . '/';
		}

		$event = (new SendNewsletterEvent($arrRecipient['email'], $objEmail->text, $objEmail->html ?? ''))
			->setHtmlAllowed(!$objNewsletter->sendText)
			->setNewsletterData($objNewsletter->row())
			->setRecipientData($arrRecipient);

		System::getContainer()->get('event_dispatcher')->dispatch($event);

		if ($event->isSkipSending())
		{
			return false;
		}

		$objEmail->text = $event->getText();
		$objEmail->html = $event->isHtmlAllowed() ? $event->getHtml() : '';
		$arrRecipient = array_merge($event->getRecipientData(), array('email' => $event->getRecipientAddress()));

		// Deactivate invalid addresses
		try
		{
			$objEmail->sendTo($arrRecipient['email']);
		}
		catch (RfcComplianceException $e)
		{
			$_SESSION['REJECTED_RECIPIENTS'][] = $arrRecipient['email'];
		}

		// Rejected recipients
		if ($objEmail->hasFailures())
		{
			$_SESSION['REJECTED_RECIPIENTS'][] = $arrRecipient['email'];
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['sendNewsletter']) && \is_array($GLOBALS['TL_HOOKS']['sendNewsletter']))
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Using the "sendNewsletter" hook has been deprecated and will no longer work in Contao 5.0. Use the SendNewsletterEvent instead.');

			foreach ($GLOBALS['TL_HOOKS']['sendNewsletter'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($objEmail, $objNewsletter, $arrRecipient, $text, $html);
			}
		}

		return true;
	}

	/**
	 * Return a form to choose a CSV file and import it
	 *
	 * @return string
	 */
	public function importRecipients()
	{
		if (Input::get('key') != 'import')
		{
			return '';
		}

		$objUploader = new FileUpload();

		// Import recipients
		if (Input::post('FORM_SUBMIT') == 'tl_recipients_import')
		{
			$arrUploaded = $objUploader->uploadTo('system/tmp');

			if (empty($arrUploaded))
			{
				Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
				$this->reload();
			}

			$time = time();
			$intTotal = 0;
			$intInvalid = 0;

			foreach ($arrUploaded as $strCsvFile)
			{
				$objFile = new File($strCsvFile);

				if ($objFile->extension != 'csv')
				{
					Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
					continue;
				}

				// Get separator
				switch (Input::post('separator'))
				{
					case 'semicolon':
						$strSeparator = ';';
						break;

					case 'tabulator':
						$strSeparator = "\t";
						break;

					case 'linebreak':
						$strSeparator = "\n";
						break;

					default:
						$strSeparator = ',';
						break;
				}

				$arrRecipients = array();
				$resFile = $objFile->handle;

				while (($arrRow = @fgetcsv($resFile, null, $strSeparator)) !== false)
				{
					$arrRecipients[] = $arrRow;
				}

				if (!empty($arrRecipients))
				{
					$arrRecipients = array_merge(...$arrRecipients);
				}

				$arrRecipients = array_filter(array_unique($arrRecipients));

				foreach ($arrRecipients as $strRecipient)
				{
					// Skip invalid entries
					if (!Validator::isEmail($strRecipient))
					{
						System::getContainer()->get('monolog.logger.contao.error')->error('The recipient address "' . $strRecipient . '" seems to be invalid and was not imported');
						++$intInvalid;
						continue;
					}

					// Check whether the e-mail address exists
					$objRecipient = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_recipients WHERE pid=? AND email=?")
												   ->execute(Input::get('id'), $strRecipient);

					if ($objRecipient->count > 0)
					{
						continue;
					}

					// Check whether the e-mail address has been added to the deny list
					$objDenyList = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_deny_list WHERE pid=? AND hash=?")
												   ->execute(Input::get('id'), md5($strRecipient));

					if ($objDenyList->count > 0)
					{
						System::getContainer()->get('monolog.logger.contao.error')->error('Recipient "' . $strRecipient . '" has unsubscribed from channel ID "' . Input::get('id') . '" and was not imported');
						continue;
					}

					$this->Database->prepare("INSERT INTO tl_newsletter_recipients SET pid=?, tstamp=$time, email=?, active=1")
								   ->execute(Input::get('id'), $strRecipient);

					++$intTotal;
				}
			}

			Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_newsletter_recipients']['confirm'], $intTotal));

			if ($intInvalid > 0)
			{
				Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_newsletter_recipients']['invalid'], $intInvalid));
			}

			$this->reload();
		}

		// Return form
		return '
<div id="tl_buttons">
<a href="' . StringUtil::ampersand(str_replace('&key=import', '', Environment::get('request'))) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
' . Message::generate() . '
<form id="tl_recipients_import" class="tl_form tl_edit_form" method="post" enctype="multipart/form-data">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_recipients_import">
<input type="hidden" name="REQUEST_TOKEN" value="' . REQUEST_TOKEN . '">
<input type="hidden" name="MAX_FILE_SIZE" value="' . Config::get('maxFileSize') . '">

<fieldset class="tl_tbox nolegend">
  <div class="widget w50">
    <h3><label for="separator">' . $GLOBALS['TL_LANG']['MSC']['separator'][0] . '</label></h3>
    <select name="separator" id="separator" class="tl_select" onfocus="Backend.getScrollOffset()">
      <option value="comma">' . $GLOBALS['TL_LANG']['MSC']['comma'] . '</option>
      <option value="semicolon">' . $GLOBALS['TL_LANG']['MSC']['semicolon'] . '</option>
      <option value="tabulator">' . $GLOBALS['TL_LANG']['MSC']['tabulator'] . '</option>
      <option value="linebreak">' . $GLOBALS['TL_LANG']['MSC']['linebreak'] . '</option>
    </select>' . ($GLOBALS['TL_LANG']['MSC']['separator'][1] ? '
    <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['MSC']['separator'][1] . '</p>' : '') . '
  </div>
  <div class="widget clr">
    <h3>' . $GLOBALS['TL_LANG']['MSC']['source'][0] . '</h3>' . $objUploader->generateMarkup() . (isset($GLOBALS['TL_LANG']['MSC']['source'][1]) ? '
    <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['MSC']['source'][1] . '</p>' : '') . '
  </div>
</fieldset>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['tl_newsletter_recipients']['import'][0] . '</button>
</div>

</div>
</form>';
	}

	/**
	 * Remove the newsletter subscriptions of members who close their account
	 *
	 * @param integer $intUser
	 * @param string  $strMode
	 */
	public function removeSubscriptions($intUser, $strMode)
	{
		if (!$intUser)
		{
			return;
		}

		// Delete or deactivate
		if ($strMode == 'close_delete')
		{
			$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE email=(SELECT email FROM tl_member WHERE id=?)")
						   ->execute($intUser);
		}
		else
		{
			$this->Database->prepare("UPDATE tl_newsletter_recipients SET active='' WHERE email=(SELECT email FROM tl_member WHERE id=?)")
						   ->execute($intUser);
		}
	}

	/**
	 * Synchronize newsletter subscription of new users
	 *
	 * @param MemberModel $intUser
	 * @param array       $arrData
	 */
	public function createNewUser($intUser, $arrData)
	{
		$arrNewsletters = StringUtil::deserialize($arrData['newsletter'] ?? '', true);

		// Return if there are no newsletters
		if (empty($arrNewsletters) || !\is_array($arrNewsletters))
		{
			return;
		}

		$time = time();

		// Add recipients
		foreach ($arrNewsletters as $intNewsletter)
		{
			$intNewsletter = (int) $intNewsletter;

			if ($intNewsletter < 1)
			{
				continue;
			}

			$objRecipient = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_recipients WHERE pid=? AND email=?")
										   ->execute($intNewsletter, $arrData['email']);

			if ($objRecipient->count < 1)
			{
				$this->Database->prepare("INSERT INTO tl_newsletter_recipients SET pid=?, tstamp=$time, email=?, addedOn=$time")
							   ->execute($intNewsletter, $arrData['email']);
			}
		}
	}

	/**
	 * Activate newsletter subscription of new users
	 *
	 * @param MemberModel $objUser
	 */
	public function activateAccount($objUser)
	{
		$arrNewsletters = StringUtil::deserialize($objUser->newsletter, true);

		// Return if there are no newsletters
		if (!\is_array($arrNewsletters))
		{
			return;
		}

		// Activate e-mail addresses
		foreach ($arrNewsletters as $intNewsletter)
		{
			$intNewsletter = (int) $intNewsletter;

			if ($intNewsletter < 1)
			{
				continue;
			}

			$this->Database->prepare("UPDATE tl_newsletter_recipients SET active='1' WHERE pid=? AND email=?")
						   ->execute($intNewsletter, $objUser->email);
		}
	}

	/**
	 * Synchronize the newsletter subscriptions if the visibility is toggled
	 *
	 * @param boolean       $blnDisabled
	 * @param DataContainer $dc
	 *
	 * @return boolean
	 */
	public function onToggleVisibility($blnDisabled, DataContainer $dc)
	{
		if (!$dc->id)
		{
			return $blnDisabled;
		}

		$objUser = $this->Database->prepare("SELECT email FROM tl_member WHERE id=?")
								  ->limit(1)
								  ->execute($dc->id);

		if ($objUser->numRows)
		{
			$this->Database->prepare("UPDATE tl_newsletter_recipients SET tstamp=?, active=? WHERE email=?")
						   ->execute(time(), ($blnDisabled ? '' : '1'), $objUser->email);
		}

		return $blnDisabled;
	}

	/**
	 * Synchronize newsletter subscription of existing users
	 *
	 * @param mixed       $varValue
	 * @param MemberModel $objUser
	 * @param ModuleModel $objModule
	 *
	 * @return mixed
	 */
	public function synchronize($varValue, $objUser, $objModule=null)
	{
		// Return if there is no user (e.g. upon registration)
		if ($objUser === null)
		{
			return $varValue;
		}

		$blnIsFrontend = true;

		// If called from the back end, the second argument is a DataContainer object
		if ($objUser instanceof DataContainer)
		{
			$objUser = $this->Database->prepare("SELECT * FROM tl_member WHERE id=?")
									  ->limit(1)
									  ->execute($objUser->id);

			if ($objUser->numRows < 1)
			{
				return $varValue;
			}

			$blnIsFrontend = false;
		}

		// Nothing has changed or e-mail address is empty
		if ($varValue == $objUser->newsletter || !$objUser->email)
		{
			return $varValue;
		}

		$time = time();
		$varValue = StringUtil::deserialize($varValue, true);

		// Get all channel IDs (thanks to Andreas Schempp)
		if ($blnIsFrontend && $objModule instanceof Module)
		{
			$arrChannel = StringUtil::deserialize($objModule->newsletters, true);
		}
		else
		{
			$arrChannel = $this->Database->query("SELECT id FROM tl_newsletter_channel")->fetchEach('id');
		}

		$arrDelete = array_values(array_diff($arrChannel, $varValue));

		// Delete existing recipients
		if (!empty($arrDelete) && \is_array($arrDelete))
		{
			$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE pid IN(" . implode(',', array_map('\intval', $arrDelete)) . ") AND email=?")
						   ->execute($objUser->email);
		}

		// Add recipients
		foreach ($varValue as $intId)
		{
			$intId = (int) $intId;

			if ($intId < 1)
			{
				continue;
			}

			$objRecipient = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_recipients WHERE pid=? AND email=?")
										   ->execute($intId, $objUser->email);

			if ($objRecipient->count < 1)
			{
				$this->Database->prepare("INSERT INTO tl_newsletter_recipients SET pid=?, tstamp=$time, email=?, active=?, addedOn=?")
							   ->execute($intId, $objUser->email, ($objUser->disable ? '' : 1), ($blnIsFrontend ? $time : ''));
			}
		}

		return serialize($varValue);
	}

	/**
	 * Update a particular member account
	 */
	public function updateAccount()
	{
		$intUser = Input::get('id');

		// Front end call
		if (TL_MODE == 'FE')
		{
			$this->import(FrontendUser::class, 'User');
			$intUser = $this->User->id;
		}

		// Return if there is no user (e.g. upon registration)
		if (!$intUser)
		{
			return;
		}

		// Edit account
		if (TL_MODE == 'FE' || Input::get('act') == 'edit')
		{
			$objUser = $this->Database->prepare("SELECT email, disable FROM tl_member WHERE id=?")
									  ->limit(1)
									  ->execute($intUser);

			if ($objUser->numRows)
			{
				$strEmail = Input::post('email', true);

				// E-mail address has changed
				if (!empty($_POST) && $strEmail && $strEmail != $objUser->email)
				{
					$objCount = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_recipients WHERE email=?")
											   ->execute($strEmail);

					// Delete the old subscription if the new e-mail address exists (see #19)
					if ($objCount->count > 0)
					{
						$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE email=?")
									   ->execute($objUser->email);
					}
					else
					{
						$this->Database->prepare("UPDATE tl_newsletter_recipients SET email=? WHERE email=?")
									   ->execute($strEmail, $objUser->email);
					}

					$objUser->email = $strEmail;
				}

				$objSubscriptions = $this->Database->prepare("SELECT pid FROM tl_newsletter_recipients WHERE email=?")
												   ->execute($objUser->email);

				if ($objSubscriptions->numRows)
				{
					$strNewsletters = serialize($objSubscriptions->fetchEach('pid'));
				}
				else
				{
					$strNewsletters = '';
				}

				$this->Database->prepare("UPDATE tl_member SET newsletter=? WHERE id=?")
							   ->execute($strNewsletters, $intUser);

				// Update the front end user object
				if (TL_MODE == 'FE')
				{
					$this->User->newsletter = $strNewsletters;
				}

				// Check activation status
				elseif (!empty($_POST) && Input::post('disable') != $objUser->disable)
				{
					$this->Database->prepare("UPDATE tl_newsletter_recipients SET active=? WHERE email=?")
								   ->execute((Input::post('disable') ? '' : 1), $objUser->email);

					$objUser->disable = Input::post('disable');
				}
			}
		}

		// Delete account
		elseif (Input::get('act') == 'delete')
		{
			$objUser = $this->Database->prepare("SELECT email FROM tl_member WHERE id=?")
									  ->limit(1)
									  ->execute($intUser);

			if ($objUser->numRows)
			{
				$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE email=?")
							   ->execute($objUser->email);
			}
		}
	}

	/**
	 * Purge subscriptions that have not been activated within 24 hours
	 */
	public function purgeSubscriptions()
	{
		$objRecipient = NewsletterRecipientsModel::findExpiredSubscriptions();

		if ($objRecipient === null)
		{
			return;
		}

		foreach ($objRecipient as $objModel)
		{
			$objModel->delete();
		}

		System::getContainer()->get('monolog.logger.contao.cron')->info('Purged the unactivated newsletter subscriptions');
	}

	/**
	 * Get all editable newsletters and return them as array
	 *
	 * @param ModuleModel $objModule
	 *
	 * @return array
	 */
	public function getNewsletters($objModule)
	{
		$objNewsletter = NewsletterChannelModel::findAll();

		if ($objNewsletter === null)
		{
			return array();
		}

		$arrNewsletters = array();

		// Return all channels if $objModule is null (see #5874)
		if ($objModule === null || TL_MODE == 'BE')
		{
			while ($objNewsletter->next())
			{
				$arrNewsletters[$objNewsletter->id] = $objNewsletter->title;
			}
		}
		else
		{
			$newsletters = StringUtil::deserialize($objModule->newsletters, true);

			if (empty($newsletters) || !\is_array($newsletters))
			{
				return array();
			}

			while ($objNewsletter->next())
			{
				if (\in_array($objNewsletter->id, $newsletters))
				{
					$arrNewsletters[$objNewsletter->id] = $objNewsletter->title;
				}
			}
		}

		natsort($arrNewsletters); // see #7864

		return $arrNewsletters;
	}

	/**
	 * Add newsletters to the indexer
	 *
	 * @param array   $arrPages
	 * @param integer $intRoot
	 * @param boolean $blnIsSitemap
	 *
	 * @return array
	 */
	public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false)
	{
		$arrRoot = array();

		if ($intRoot > 0)
		{
			$arrRoot = $this->Database->getChildRecords($intRoot, 'tl_page');
		}

		$arrProcessed = array();
		$time = time();

		// Get all channels
		$objNewsletter = NewsletterChannelModel::findAll();

		// Walk through each channel
		if ($objNewsletter !== null)
		{
			while ($objNewsletter->next())
			{
				if (!$objNewsletter->jumpTo)
				{
					continue;
				}

				// Skip channels outside the root nodes
				if (!empty($arrRoot) && !\in_array($objNewsletter->jumpTo, $arrRoot))
				{
					continue;
				}

				// Get the URL of the jumpTo page
				if (!isset($arrProcessed[$objNewsletter->jumpTo]))
				{
					$objParent = PageModel::findWithDetails($objNewsletter->jumpTo);

					// The target page does not exist
					if ($objParent === null)
					{
						continue;
					}

					// The target page has not been published (see #5520)
					if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time))
					{
						continue;
					}

					if ($blnIsSitemap)
					{
						// The target page is protected (see #8416)
						if ($objParent->protected)
						{
							continue;
						}

						// The target page is exempt from the sitemap (see #6418)
						if ($objParent->robots == 'noindex,nofollow')
						{
							continue;
						}
					}

					// Generate the URL
					$arrProcessed[$objNewsletter->jumpTo] = $objParent->getAbsoluteUrl(Config::get('useAutoItem') ? '/%s' : '/items/%s');
				}

				$strUrl = $arrProcessed[$objNewsletter->jumpTo];

				// Get the items
				$objItem = NewsletterModel::findSentByPid($objNewsletter->id);

				if ($objItem !== null)
				{
					while ($objItem->next())
					{
						$arrPages[] = sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItem->alias ?: $objItem->id));
					}
				}
			}
		}

		return $arrPages;
	}
}

class_alias(Newsletter::class, 'Newsletter');
