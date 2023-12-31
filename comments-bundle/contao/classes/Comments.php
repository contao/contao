<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Nyholm\Psr7\Uri;

/**
 * Class Comments
 */
class Comments extends Frontend
{
	/**
	 * Add comments to a template
	 *
	 * @param FrontendTemplate $objTemplate
	 * @param \stdClass        $objConfig
	 * @param string           $strSource
	 * @param integer          $intParent
	 * @param mixed            $varNotifies
	 */
	public function addCommentsToTemplate(FrontendTemplate $objTemplate, \stdClass $objConfig, $strSource, $intParent, $varNotifies)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$limit = 0;
		$offset = 0;
		$total = 0;
		$gtotal = 0;
		$arrComments = array();

		$objTemplate->comments = array(); // see #4064

		// Pagination
		if ($objConfig->perPage > 0)
		{
			// Get the total number of comments
			$intTotal = CommentsModel::countPublishedBySourceAndParent($strSource, $intParent);
			$total = $gtotal = $intTotal;

			// Calculate the key (e.g. tl_form_field becomes page_cff12)
			$key = '';
			$chunks = explode('_', substr($strSource, (strncmp($strSource, 'tl_', 3) === 0) ? 3 : 0));

			foreach ($chunks as $chunk)
			{
				$key .= substr($chunk, 0, 1);
			}

			// Get the current page
			$id = 'page_c' . $key . $intParent; // see #4141
			$page = (int) (Input::get($id) ?? 1);

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$objConfig->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}

			// Set limit and offset
			$limit = $objConfig->perPage;
			$offset = ($page - 1) * $objConfig->perPage;

			// Initialize the pagination menu
			$objPagination = new Pagination($total, $objConfig->perPage, Config::get('maxPaginationLinks'), $id);
			$objTemplate->pagination = $objPagination->generate("\n  ");
		}

		$objTemplate->allowComments = true;

		// Get all published comments
		if ($limit)
		{
			$objComments = CommentsModel::findPublishedBySourceAndParent($strSource, $intParent, $objConfig->order == 'descending', $limit, $offset);
		}
		else
		{
			$objComments = CommentsModel::findPublishedBySourceAndParent($strSource, $intParent, $objConfig->order == 'descending');
		}

		// Parse the comments
		if ($objComments !== null && ($total = $objComments->count()) > 0)
		{
			$tags = array();
			$objPartial = new FrontendTemplate($objConfig->template ?: 'com_default');

			while ($objComments->next())
			{
				$objPartial->setData($objComments->row());

				// Clean the RTE output
				$objPartial->comment = $objComments->comment;
				$objPartial->comment = trim(str_replace(array('{{', '}}'), array('&#123;&#123;', '&#125;&#125;'), $objPartial->comment));

				$objPartial->datim = Date::parse($objPage->datimFormat, $objComments->date);
				$objPartial->date = Date::parse($objPage->dateFormat, $objComments->date);
				$objPartial->by = $GLOBALS['TL_LANG']['MSC']['com_by'];
				$objPartial->id = 'c' . $objComments->id;
				$objPartial->timestamp = $objComments->date;
				$objPartial->datetime = date('Y-m-d\TH:i:sP', $objComments->date);
				$objPartial->addReply = false;

				// Reply
				if ($objComments->addReply && $objComments->reply && ($objAuthor = $objComments->getRelated('author')) instanceof UserModel)
				{
					$objPartial->addReply = true;
					$objPartial->rby = $GLOBALS['TL_LANG']['MSC']['com_reply'];
					$objPartial->reply = System::getContainer()->get('contao.insert_tag.parser')->replace($objComments->reply);
					$objPartial->author = $objAuthor;
				}

				$arrComments[] = $objPartial->parse();
				$tags[] = 'contao.db.tl_comments.' . $objComments->id;
			}

			// Tag the comments (see #2137)
			if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
			{
				$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
				$responseTagger->addTags($tags);
			}
		}

		$objTemplate->comments = $arrComments;
		$objTemplate->hlcText = $GLOBALS['TL_LANG']['MSC']['comments'];
		$objTemplate->addComment = $GLOBALS['TL_LANG']['MSC']['addComment'];
		$objTemplate->name = $GLOBALS['TL_LANG']['MSC']['com_name'];
		$objTemplate->email = $GLOBALS['TL_LANG']['MSC']['com_email'];
		$objTemplate->website = $GLOBALS['TL_LANG']['MSC']['com_website'];
		$objTemplate->commentsTotal = $limit ? $gtotal : $total;

		// Add a form to create new comments
		$this->renderCommentForm($objTemplate, $objConfig, $strSource, $intParent, $varNotifies);
	}

	/**
	 * Add a form to create new comments
	 *
	 * @param FrontendTemplate $objTemplate
	 * @param \stdClass        $objConfig
	 * @param string           $strSource
	 * @param integer          $intParent
	 * @param mixed            $varNotifies
	 */
	protected function renderCommentForm(FrontendTemplate $objTemplate, \stdClass $objConfig, $strSource, $intParent, $varNotifies)
	{
		// Access control
		if ($objConfig->requireLogin && !System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
		{
			$objTemplate->requireLogin = true;
			$objTemplate->login = $GLOBALS['TL_LANG']['MSC']['com_login'];

			return;
		}

		// Confirm or remove a subscription
		if (strncmp(Input::get('token'), 'com-', 4) === 0 || strncmp(Input::get('token'), 'cor-', 4) === 0)
		{
			static::changeSubscriptionStatus($objTemplate);

			return;
		}

		$user = FrontendUser::getInstance();

		// Form fields
		$arrFields = array
		(
			'name' => array
			(
				'name'      => 'name',
				'label'     => $GLOBALS['TL_LANG']['MSC']['com_name'],
				'value'     => trim($user->firstname . ' ' . $user->lastname),
				'inputType' => 'text',
				'eval'      => array('mandatory'=>true, 'maxlength'=>64)
			),
			'email' => array
			(
				'name'      => 'email',
				'label'     => $GLOBALS['TL_LANG']['MSC']['com_email'],
				'value'     => $user->email,
				'inputType' => 'text',
				'eval'      => array('rgxp'=>'email', 'mandatory'=>true, 'maxlength'=>255, 'decodeEntities'=>true)
			),
			'website' => array
			(
				'name'      => 'website',
				'label'     => $GLOBALS['TL_LANG']['MSC']['com_website'],
				'inputType' => 'text',
				'eval'      => array('rgxp'=>HttpUrlListener::RGXP_NAME, 'maxlength'=>128, 'decodeEntities'=>true)
			)
		);

		// Captcha
		if (!$objConfig->disableCaptcha)
		{
			$arrFields['captcha'] = array
			(
				'name'      => 'captcha',
				'label'     => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval'      => array('mandatory'=>true)
			);
		}

		// Comment field
		$arrFields['comment'] = array
		(
			'name'      => 'comment',
			'label'     => $GLOBALS['TL_LANG']['MSC']['com_comment'],
			'inputType' => 'textarea',
			'eval'      => array('mandatory'=>true, 'rows'=>4, 'cols'=>40, 'preserveTags'=>true)
		);

		// Notify me of new comments
		$arrFields['notify'] = array
		(
			'name'      => 'notify',
			'label'     => '',
			'inputType' => 'checkbox',
			'options'   => array(1=>$GLOBALS['TL_LANG']['MSC']['com_notify'])
		);

		$doNotSubmit = false;
		$arrWidgets = array();
		$strFormId = 'com_' . $strSource . '_' . $intParent;

		// Initialize the widgets
		foreach ($arrFields as $arrField)
		{
			$strClass = $GLOBALS['TL_FFL'][$arrField['inputType']] ?? null;

			// Continue if the class is not defined
			if (!class_exists($strClass))
			{
				continue;
			}

			$arrField['eval']['required'] = $arrField['eval']['mandatory'] ?? null;

			/** @var Widget $objWidget */
			$objWidget = new $strClass($strClass::getAttributesFromDca($arrField, $arrField['name'], $arrField['value'] ?? null));

			// Append the parent ID to prevent duplicate IDs (see #1493)
			$objWidget->id .= '_' . $intParent;

			// Validate the widget
			if (Input::post('FORM_SUBMIT') == $strFormId)
			{
				$objWidget->validate();

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
			}

			$arrWidgets[$arrField['name']] = $objWidget;
		}

		$objTemplate->fields = $arrWidgets;
		$objTemplate->submit = $GLOBALS['TL_LANG']['MSC']['com_submit'];
		$objTemplate->formId = $strFormId;
		$objTemplate->hasError = $doNotSubmit;

		$session = System::getContainer()->get('request_stack')->getSession();

		// Do not index or cache the page with the confirmation message
		if ($session->isStarted())
		{
			$flashBag = $session->getFlashBag();

			if ($flashBag->has('comment_added'))
			{
				$objTemplate->confirm = $flashBag->get('comment_added')[0];
			}
		}

		// Store the comment
		if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
		{
			$strWebsite = $arrWidgets['website']->value;

			// Add http:// to the website
			if ($strWebsite && !preg_match('@^(https?://|ftp://|mailto:|#)@i', $strWebsite))
			{
				$strWebsite = 'http://' . $strWebsite;
			}

			// Do not parse any tags in the comment
			$strComment = StringUtil::specialchars(trim($arrWidgets['comment']->value));

			// Remove multiple line feeds
			$strComment = preg_replace('@\n\n+@', "\n\n", $strComment);

			// Parse BBCode
			if ($objConfig->bbcode)
			{
				$strComment = $this->parseBbCode($strComment);
			}

			// Prevent cross-site request forgeries
			$strComment = preg_replace('/(href|src|on[a-z]+)="[^"]*(contao\/main\.php|typolight\/main\.php|javascript|vbscri?pt|script|alert|document|cookie|window)[^"]*"+/i', '$1="#"', $strComment);

			$intMember = 0;

			if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
			{
				$intMember = FrontendUser::getInstance()->id;
			}

			$time = time();

			// Prepare the record
			$arrSet = array
			(
				'tstamp'    => $time,
				'source'    => $strSource,
				'parent'    => $intParent,
				'name'      => $arrWidgets['name']->value,
				'email'     => $arrWidgets['email']->value,
				'website'   => $strWebsite,
				'member'    => $intMember,
				'comment'   => $this->convertLineFeeds($strComment),
				'ip'        => Environment::get('ip'),
				'date'      => $time,
				'published' => ($objConfig->moderate ? '' : 1)
			);

			// Store the comment
			$objComment = new CommentsModel();
			$objComment->setRow($arrSet)->save();

			// Store the subscription
			if ($arrWidgets['notify']->value)
			{
				static::addCommentsSubscription($objComment);
			}

			// HOOK: add custom logic
			if (isset($GLOBALS['TL_HOOKS']['addComment']) && \is_array($GLOBALS['TL_HOOKS']['addComment']))
			{
				foreach ($GLOBALS['TL_HOOKS']['addComment'] as $callback)
				{
					System::importStatic($callback[0])->{$callback[1]}($objComment->id, $arrSet, $this);
				}
			}

			// Prepare the notification mail
			$objEmail = new Email();
			$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'] ?? null;
			$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'] ?? null;
			$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['com_subject'], Idna::decode(Environment::get('host')));

			// Convert the comment to plain text
			$strComment = strip_tags($strComment);
			$strComment = StringUtil::decodeEntities($strComment);

			// Add the comment details
			$objEmail->text = sprintf(
				$GLOBALS['TL_LANG']['MSC']['com_message'],
				$arrSet['name'] . ' (' . $arrSet['email'] . ')',
				$strComment,
				Idna::decode(Environment::get('url')) . Environment::get('requestUri'),
				Idna::decode(Environment::get('base')) . 'contao?do=comments&act=edit&id=' . $objComment->id
			);

			// Add a moderation hint to the e-mail (see #7478)
			if ($objConfig->moderate)
			{
				$objEmail->text .= "\n" . $GLOBALS['TL_LANG']['MSC']['com_moderated'] . "\n";
			}

			// Do not send notifications twice
			if (\is_array($varNotifies))
			{
				$objEmail->sendTo(array_unique($varNotifies));
			}
			elseif ($varNotifies)
			{
				$objEmail->sendTo($varNotifies); // see #5443
			}

			// Pending for approval
			if ($objConfig->moderate)
			{
				$session->getFlashBag()->set('comment_added', $GLOBALS['TL_LANG']['MSC']['com_confirm']);
			}
			else
			{
				static::notifyCommentsSubscribers($objComment);
			}

			$this->reload();
		}
	}

	/**
	 * Replace bbcode and return the HTML string
	 *
	 * Supports the following tags:
	 *
	 * * [b][/b] bold
	 * * [i][/i] italic
	 * * [u][/u] underline
	 * * [img][/img]
	 * * [code][/code]
	 * * [color=#ff0000][/color]
	 * * [quote][/quote]
	 * * [quote=tim][/quote]
	 * * [url][/url]
	 * * [url=http://][/url]
	 * * [email][/email]
	 * * [email=name@example.com][/email]
	 *
	 * @param string $strComment
	 *
	 * @return string
	 */
	public function parseBbCode($strComment)
	{
		$arrSearch = array
		(
			'@\[b\](.*)\[/b\]@Uis',
			'@\[i\](.*)\[/i\]@Uis',
			'@\[u\](.*)\[/u\]@Uis',
			'@\s*\[code\](.*)\[/code\]\s*@Uis',
			'@\[color=([^\]" ]+)\](.*)\[/color\]@Uis',
			'@\s*\[quote\](.*)\[/quote\]\s*@Uis',
			'@\s*\[quote=([^\]]+)\](.*)\[/quote\]\s*@Uis',
			'@\[img\]\s*([^\[" ]+\.(jpe?g|png|gif|bmp|tiff?|ico))\s*\[/img\]@i',
			'@\[url\]\s*([^\[" ]+)\s*\[/url\]@i',
			'@\[url=([^\]" ]+)\](.*)\[/url\]@Uis',
			'@\[email\]\s*([^\[" ]+)\s*\[/email\]@i',
			'@\[email=([^\]" ]+)\](.*)\[/email\]@Uis',
			'@href="(([a-z0-9]+\.)*[a-z0-9]+\.([a-z]{2}|asia|biz|com|info|name|net|org|tel)(/|"))@i'
		);

		$arrReplace = array
		(
			'<strong>$1</strong>',
			'<em>$1</em>',
			'<span style="text-decoration:underline">$1</span>',
			"\n\n" . '<div class="code"><p>' . $GLOBALS['TL_LANG']['MSC']['com_code'] . '</p><pre>$1</pre></div>' . "\n\n",
			'<span style="color:$1">$2</span>',
			"\n\n" . '<blockquote>$1</blockquote>' . "\n\n",
			"\n\n" . '<blockquote><p>' . sprintf($GLOBALS['TL_LANG']['MSC']['com_quote'], '$1') . '</p>$2</blockquote>' . "\n\n",
			'<img src="$1" alt="" />',
			'<a href="$1">$1</a>',
			'<a href="$1">$2</a>',
			'<a href="mailto:$1">$1</a>',
			'<a href="mailto:$1">$2</a>',
			'href="http://$1'
		);

		$strComment = preg_replace($arrSearch, $arrReplace, $strComment);

		// Encode e-mail addresses
		if (strpos($strComment, 'mailto:') !== false)
		{
			$strComment = StringUtil::encodeEmail($strComment);
		}

		return $strComment;
	}

	/**
	 * Convert line feeds to <br /> tags
	 *
	 * @param string $strComment
	 *
	 * @return string
	 */
	public function convertLineFeeds($strComment)
	{
		$strComment = preg_replace('/\r?\n/', '<br>', $strComment);

		// Use paragraphs to generate new lines
		if (strncmp('<p>', $strComment, 3) !== 0)
		{
			$strComment = '<p>' . $strComment . '</p>';
		}

		$arrReplace = array
		(
			'@<br>\s?<br>\s?@' => "</p>\n<p>", // Convert two linebreaks into a new paragraph
			'@\s?<br></p>@'    => '</p>',      // Remove BR tags before closing P tags
			'@<p><div@'        => '<div',      // Do not nest DIVs inside paragraphs
			'@</div></p>@'     => '</div>'     // Do not nest DIVs inside paragraphs
		);

		return preg_replace(array_keys($arrReplace), array_values($arrReplace), $strComment);
	}

	/**
	 * Purge subscriptions that have not been activated within 24 hours
	 *
	 * @deprecated Deprecated since Contao 5.0, to be removed in Contao 6.0.
	 *             Use CommentsNotifyModel::findExpiredSubscriptions() instead.
	 */
	public function purgeSubscriptions()
	{
		trigger_deprecation('contao/comments-bundle', '5.0', 'Calling "%s()" has been deprecated and will no longer work in Contao 6. Use "CommentsNotifyModel::findExpiredSubscriptions()" instead.', __METHOD__);

		$objNotify = CommentsNotifyModel::findExpiredSubscriptions();

		if ($objNotify === null)
		{
			return;
		}

		while ($objNotify->next())
		{
			$objNotify->delete();
		}

		System::getContainer()->get('monolog.logger.contao.cron')->info('Purged the unactivated comment subscriptions');
	}

	/**
	 * Add the subscription and send the activation mail (double opt-in)
	 *
	 * @param CommentsModel $objComment
	 */
	public static function addCommentsSubscription(CommentsModel $objComment)
	{
		$objNotify = CommentsNotifyModel::findBySourceParentAndEmail($objComment->source, $objComment->parent, $objComment->email);

		// The subscription exists already
		if ($objNotify !== null)
		{
			return;
		}

		$time = time();

		// Ensure that the URL only contains ASCII characters (see #4708)
		$request = (string) (new Uri(Environment::get('requestUri')));

		// Prepare the record
		$arrSet = array
		(
			'tstamp'       => $time,
			'source'       => $objComment->source,
			'parent'       => $objComment->parent,
			'name'         => $objComment->name,
			'email'        => $objComment->email,
			'url'          => $request,
			'addedOn'      => $time,
			'active'       => false,
			'tokenRemove'  => 'cor-' . bin2hex(random_bytes(10))
		);

		// Store the subscription
		$objNotify = new CommentsNotifyModel();
		$objNotify->setRow($arrSet)->save();

		$strUrl = Idna::decode(Environment::get('base')) . $request;
		$strConnector = (strpos($strUrl, '?') !== false) ? '&' : '?';

		$optIn = System::getContainer()->get('contao.opt_in');
		$optInToken = $optIn->create('com', $objComment->email, array('tl_comments_notify'=>array($objNotify->id)));

		// Send the token
		$optInToken->send(sprintf($GLOBALS['TL_LANG']['MSC']['com_optInSubject'], Idna::decode(Environment::get('host'))), sprintf($GLOBALS['TL_LANG']['MSC']['com_optInMessage'], $objComment->name, $strUrl, $strUrl . $strConnector . 'token=' . $optInToken->getIdentifier(), $strUrl . $strConnector . 'token=' . $objNotify->tokenRemove));
	}

	/**
	 * Change the subscription status
	 *
	 * @param FrontendTemplate $objTemplate
	 */
	public static function changeSubscriptionStatus(FrontendTemplate $objTemplate)
	{
		if (strncmp(Input::get('token'), 'com-', 4) === 0)
		{
			$optIn = System::getContainer()->get('contao.opt_in');

			// Find an unconfirmed token with only one related record
			if ((!$optInToken = $optIn->find(Input::get('token'))) || !$optInToken->isValid() || \count($arrRelated = $optInToken->getRelatedRecords()) != 1 || key($arrRelated) != 'tl_comments_notify' || \count($arrIds = current($arrRelated)) != 1 || (!$objNotify = CommentsNotifyModel::findByPk($arrIds[0])))
			{
				$objTemplate->confirm = $GLOBALS['TL_LANG']['MSC']['invalidToken'];

				return;
			}

			if ($optInToken->isConfirmed())
			{
				$objTemplate->confirm = $GLOBALS['TL_LANG']['MSC']['tokenConfirmed'];

				return;
			}

			if ($optInToken->getEmail() != $objNotify->email)
			{
				$objTemplate->confirm = $GLOBALS['TL_LANG']['MSC']['tokenEmailMismatch'];

				return;
			}

			$objNotify->active = true;
			$objNotify->save();

			$optInToken->confirm();

			$objTemplate->confirm = $GLOBALS['TL_LANG']['MSC']['com_optInConfirm'];
		}
		elseif (strncmp(Input::get('token'), 'cor-', 4) === 0)
		{
			$objNotify = CommentsNotifyModel::findOneByTokenRemove(Input::get('token'));

			if ($objNotify === null)
			{
				$objTemplate->confirm = $GLOBALS['TL_LANG']['MSC']['invalidToken'];

				return;
			}

			$objNotify->delete();
			$objTemplate->confirm = $GLOBALS['TL_LANG']['MSC']['com_optInCancel'];
		}
	}

	/**
	 * Notify the subscribers of new comments
	 *
	 * @param CommentsModel $objComment
	 */
	public static function notifyCommentsSubscribers(CommentsModel $objComment)
	{
		// Notified already
		if ($objComment->notified)
		{
			return;
		}

		$objNotify = CommentsNotifyModel::findActiveBySourceAndParent($objComment->source, $objComment->parent);

		if ($objNotify !== null)
		{
			$request = System::getContainer()->get('request_stack')->getCurrentRequest();
			$isFrontend = $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request);

			while ($objNotify->next())
			{
				// Don't notify the commenter about his own comment
				if ($objNotify->email == $objComment->email)
				{
					continue;
				}

				// Update the notification URL if it has changed (see #373)
				if ($isFrontend && $objNotify->url != Environment::get('requestUri'))
				{
					$objNotify->url = Environment::get('requestUri');
					$objNotify->save();
				}

				// Prepare the URL
				$strUrl = Idna::decode(Environment::get('base')) . $objNotify->url;

				$objEmail = new Email();
				$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'] ?? null;
				$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'] ?? null;
				$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['com_notifySubject'], Idna::decode(Environment::get('host')));
				$objEmail->text = sprintf($GLOBALS['TL_LANG']['MSC']['com_notifyMessage'], $objNotify->name, $strUrl . '#c' . $objComment->id, $strUrl . '?token=' . $objNotify->tokenRemove);
				$objEmail->sendTo($objNotify->email);
			}
		}

		$objComment->notified = true;
		$objComment->save();
	}
}
