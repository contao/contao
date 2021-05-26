<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\BackendUser;
use Contao\Cache;
use Contao\Comments;
use Contao\CommentsModel;
use Contao\CommentsNotifyModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Date;
use Contao\Email;
use Contao\Environment;
use Contao\Idna;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

$GLOBALS['TL_DCA']['tl_comments'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'closed'                      => true,
		'notCopyable'                 => true,
		'onload_callback' => array
		(
			array('tl_comments', 'checkPermission')
		),
		'onsubmit_callback' => array
		(
			array('tl_comments', 'notifyOfReply')
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_comments', 'invalidateSourceCacheTags')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'published' => 'index',
				'source,parent,published' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SWITCHABLE_FIELD,
			'fields'                  => array('date'),
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s',
			'label_callback'          => array('tl_comments', 'listComments')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'edit.svg',
				'button_callback'     => array('tl_comments', 'editComment')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_comments', 'deleteComment')
			),
			'toggle' => array
			(
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_comments', 'toggleIcon')
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('addReply'),
		'default'                     => '{author_legend},name,member,email,website;{comment_legend},comment;{reply_legend},addReply;{publish_legend},published'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'addReply'                    => 'author,reply'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'source' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'reference'               => &$GLOBALS['TL_LANG']['tl_comments'],
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'parent' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'date' => array
		(
			'sorting'                 => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'name' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'email' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'rgxp'=>'email', 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'website' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>128, 'rgxp'=>HttpUrlListener::RGXP_NAME, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'member' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_member.CONCAT(firstname," ",lastname)',
			'eval'                    => array('chosen'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'comment' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'rte'=>'tinyMCE'),
			'sql'                     => "text NULL"
		),
		'addReply' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'author' => array
		(
			'default'                 => BackendUser::getInstance()->id,
			'exclude'                 => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('mandatory'=>true, 'chosen'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'reply' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'published' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'save_callback' => array
			(
				array('tl_comments', 'sendNotifications')
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'ip' => array
		(
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'notified' => array
		(
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'notifiedReply' => array
		(
			'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_comments extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	/**
	 * Check permissions to edit table tl_comments
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		switch (Input::get('act'))
		{
			case 'select':
			case 'show':
				// Allow
				break;

			case 'edit':
			case 'delete':
			case 'toggle':
				$objComment = $this->Database->prepare("SELECT id, parent, source FROM tl_comments WHERE id=?")
											 ->limit(1)
											 ->execute(Input::get('id'));

				if ($objComment->numRows < 1)
				{
					throw new AccessDeniedException('Invalid comment ID ' . Input::get('id') . '.');
				}

				if (!$this->isAllowedToEditComment($objComment->parent, $objComment->source))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' comment ID ' . Input::get('id') . ' (parent element: ' . $objComment->source . ' ID ' . $objComment->parent . ').');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
				/** @var SessionInterface $objSession */
				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();

				if (empty($session['CURRENT']['IDS']) || !is_array($session['CURRENT']['IDS']))
				{
					break;
				}

				$objComment = $this->Database->execute("SELECT id, parent, source FROM tl_comments WHERE id IN(" . implode(',', array_map('\intval', $session['CURRENT']['IDS'])) . ")");

				while ($objComment->next())
				{
					if (!$this->isAllowedToEditComment($objComment->parent, $objComment->source) && ($key = array_search($objComment->id, $session['CURRENT']['IDS'])) !== false)
					{
						unset($session['CURRENT']['IDS'][$key]);
					}
				}

				$session['CURRENT']['IDS'] = array_values($session['CURRENT']['IDS']);
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Invalid command "' . Input::get('act') . '.');
				}
				break;
		}
	}

	/**
	 * Notify subscribers of a reply
	 *
	 * @param DataContainer $dc
	 */
	public function notifyOfReply(DataContainer $dc)
	{
		// Return if there is no active record (override all) or no reply or the notification has been sent already
		if (!$dc->activeRecord || !$dc->activeRecord->addReply || $dc->activeRecord->notifyReply)
		{
			return;
		}

		$objNotify = CommentsNotifyModel::findActiveBySourceAndParent($dc->activeRecord->source, $dc->activeRecord->parent);

		if ($objNotify !== null)
		{
			while ($objNotify->next())
			{
				// Prepare the URL
				$strUrl = Idna::decode(Environment::get('base')) . $objNotify->url;

				$objEmail = new Email();
				$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
				$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
				$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['com_notifyReplySubject'], Idna::decode(Environment::get('host')));
				$objEmail->text = sprintf($GLOBALS['TL_LANG']['MSC']['com_notifyReplyMessage'], $objNotify->name, $strUrl . '#c' . $dc->id, $strUrl . '?token=' . $objNotify->tokenRemove);
				$objEmail->sendTo($objNotify->email);
			}
		}

		$this->Database->prepare("UPDATE tl_comments SET notifiedReply='1' WHERE id=?")->execute($dc->id);
	}

	/**
	 * Check whether the user is allowed to edit a comment
	 *
	 * @param integer $intParent
	 * @param string  $strSource
	 *
	 * @return boolean
	 */
	protected function isAllowedToEditComment($intParent, $strSource)
	{
		if ($this->User->isAdmin)
		{
			return true;
		}

		$strKey = __METHOD__ . '-' . $strSource . '-' . $intParent;

		// Load cached result
		if (Cache::has($strKey))
		{
			return Cache::get($strKey);
		}

		// Order deny,allow
		Cache::set($strKey, false);

		switch ($strSource)
		{
			case 'tl_content':
				$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=(SELECT pid FROM tl_article WHERE id=(SELECT pid FROM tl_content WHERE id=?))")
										  ->limit(1)
										  ->execute($intParent);

				// Do not check whether the page is mounted (see #5174)
				if ($objPage->numRows > 0 && $this->User->isAllowed(BackendUser::CAN_EDIT_ARTICLES, $objPage->row()))
				{
					Cache::set($strKey, true);
				}
				break;

			case 'tl_page':
				$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
										  ->limit(1)
										  ->execute($intParent);

				// Do not check whether the page is mounted (see #5174)
				if ($objPage->numRows > 0 && $this->User->isAllowed(BackendUser::CAN_EDIT_PAGE, $objPage->row()))
				{
					Cache::set($strKey, true);
				}
				break;

			case 'tl_news':
				$objArchive = $this->Database->prepare("SELECT pid FROM tl_news WHERE id=?")
											 ->limit(1)
											 ->execute($intParent);

				// Do not check the access to the news module (see #5174)
				if ($objArchive->numRows > 0 && $this->User->hasAccess($objArchive->pid, 'news'))
				{
					Cache::set($strKey, true);
				}
				break;

			case 'tl_calendar_events':
				$objCalendar = $this->Database->prepare("SELECT pid FROM tl_calendar_events WHERE id=?")
											  ->limit(1)
											  ->execute($intParent);

				// Do not check the access to the calendar module (see #5174)
				if ($objCalendar->numRows > 0 && $this->User->hasAccess($objCalendar->pid, 'calendars'))
				{
					Cache::set($strKey, true);
				}
				break;

			case 'tl_faq':
				// Do not check access to the FAQ module (see #5174)
				Cache::set($strKey, true);
				break;

			default:
				// HOOK: support custom modules
				if (isset($GLOBALS['TL_HOOKS']['isAllowedToEditComment']) && is_array($GLOBALS['TL_HOOKS']['isAllowedToEditComment']))
				{
					foreach ($GLOBALS['TL_HOOKS']['isAllowedToEditComment'] as $callback)
					{
						$this->import($callback[0]);

						if ($this->{$callback[0]}->{$callback[1]}($intParent, $strSource) === true)
						{
							Cache::set($strKey, true);
							break;
						}
					}
				}
				break;
		}

		return Cache::get($strKey);
	}

	/**
	 * Send out the new comment notifications
	 *
	 * @param mixed $varValue
	 *
	 * @return mixed
	 */
	public function sendNotifications($varValue)
	{
		if ($varValue)
		{
			Comments::notifyCommentsSubscribers(CommentsModel::findByPk(Input::get('id')));
		}

		return $varValue;
	}

	/**
	 * List a particular record
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listComments($arrRow)
	{
		$title = $GLOBALS['TL_LANG']['tl_comments'][$arrRow['source']] . ' ' . $arrRow['parent'];

		switch ($arrRow['source'])
		{
			case 'tl_content':
				$objParent = $this->Database->prepare("SELECT id, title FROM tl_article WHERE id=(SELECT pid FROM tl_content WHERE id=?)")
											->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="contao/main.php?do=article&amp;table=tl_content&amp;id=' . $objParent->id . '&amp;rt=' . REQUEST_TOKEN . '">' . $objParent->title . '</a>';
				}
				break;

			case 'tl_page':
				$objParent = $this->Database->prepare("SELECT id, title FROM tl_page WHERE id=?")
											->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="contao/main.php?do=page&amp;act=edit&amp;id=' . $objParent->id . '&amp;rt=' . REQUEST_TOKEN . '">' . $objParent->title . '</a>';
				}
				break;

			case 'tl_news':
				$objParent = $this->Database->prepare("SELECT id, headline FROM tl_news WHERE id=?")
											->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="contao/main.php?do=news&amp;table=tl_news&amp;act=edit&amp;id=' . $objParent->id . '&amp;rt=' . REQUEST_TOKEN . '">' . $objParent->headline . '</a>';
				}
				break;

			case 'tl_faq':
				$objParent = $this->Database->prepare("SELECT id, question FROM tl_faq WHERE id=?")
											->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="contao/main.php?do=faq&amp;table=tl_faq&amp;act=edit&amp;id=' . $objParent->id . '&amp;rt=' . REQUEST_TOKEN . '">' . $objParent->question . '</a>';
				}
				break;

			case 'tl_calendar_events':
				$objParent = $this->Database->prepare("SELECT id, title FROM tl_calendar_events WHERE id=?")
											->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="contao/main.php?do=calendar&amp;table=tl_calendar_events&amp;act=edit&amp;id=' . $objParent->id . '&amp;rt=' . REQUEST_TOKEN . '">' . $objParent->title . '</a>';
				}
				break;

			default:
				// HOOK: support custom modules
				if (isset($GLOBALS['TL_HOOKS']['listComments']) && is_array($GLOBALS['TL_HOOKS']['listComments']))
				{
					foreach ($GLOBALS['TL_HOOKS']['listComments'] as $callback)
					{
						$this->import($callback[0]);

						if ($tmp = $this->{$callback[0]}->{$callback[1]}($arrRow))
						{
							$title .= $tmp;
							break;
						}
					}
				}
				break;
		}

		$key = ($arrRow['published'] ? 'published' : 'unpublished') . ($arrRow['addReply'] ? ' replied' : '');

		return '
<div class="comment_wrap">
<div class="cte_type ' . $key . '"><a href="mailto:' . Idna::decodeEmail($arrRow['email']) . '" title="' . StringUtil::specialchars(Idna::decodeEmail($arrRow['email'])) . '">' . $arrRow['name'] . '</a>' . ($arrRow['website'] ? ' (<a href="' . $arrRow['website'] . '" title="' . StringUtil::specialchars($arrRow['website']) . '" target="_blank" rel="noreferrer noopener">' . $GLOBALS['TL_LANG']['MSC']['com_website'] . '</a>)' : '') . ' – ' . Date::parse(Config::get('datimFormat'), $arrRow['date']) . ' – IP ' . StringUtil::specialchars($arrRow['ip']) . '<br>' . $title . '</div>
<div class="limit_height mark_links' . (!Config::get('doNotCollapse') ? ' h40' : '') . '">
' . $arrRow['comment'] . '
</div>
</div>' . "\n    ";
	}

	/**
	 * Return the edit comment button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function editComment($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->isAllowedToEditComment($row['parent'], $row['source']) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the delete comment button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function deleteComment($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->isAllowedToEditComment($row['parent'], $row['source']) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the "toggle visibility" button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if (Input::get('tid'))
		{
			$this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1), (@func_get_arg(12) ?: null));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->hasAccess('tl_comments::published', 'alexf'))
		{
			return '';
		}

		$href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['published'] ? '' : 1);

		if (!$row['published'])
		{
			$icon = 'invisible.svg';
		}

		if (!$this->isAllowedToEditComment($row['parent'], $row['source']))
		{
			return Image::getHtml($icon) . ' ';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label, 'data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
	}

	/**
	 * Disable/enable a user group
	 *
	 * @param integer       $intId
	 * @param boolean       $blnVisible
	 * @param DataContainer $dc
	 *
	 * @throws AccessDeniedException
	 */
	public function toggleVisibility($intId, $blnVisible, DataContainer $dc=null)
	{
		// Set the ID and action
		Input::setGet('id', $intId);
		Input::setGet('act', 'toggle');

		if ($dc)
		{
			$dc->id = $intId; // see #8043
		}

		// Trigger the onload_callback
		if (is_array($GLOBALS['TL_DCA']['tl_comments']['config']['onload_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA']['tl_comments']['config']['onload_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		// Check the field access
		if (!$this->User->hasAccess('tl_comments::published', 'alexf'))
		{
			throw new AccessDeniedException('Not enough permissions to publish/unpublish comment ID ' . $intId . '.');
		}

		$objRow = $this->Database->prepare("SELECT * FROM tl_comments WHERE id=?")
								 ->limit(1)
								 ->execute($intId);

		if ($objRow->numRows < 1)
		{
			throw new AccessDeniedException('Invalid comment ID ' . $intId . '.');
		}

		// Set the current record
		if ($dc)
		{
			$dc->activeRecord = $objRow;
		}

		$objVersions = new Versions('tl_comments', $intId);
		$objVersions->initialize();

		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_comments']['fields']['published']['save_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA']['tl_comments']['fields']['published']['save_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
				}
				elseif (is_callable($callback))
				{
					$blnVisible = $callback($blnVisible, $dc);
				}
			}
		}

		$time = time();

		// Update the database
		$this->Database->prepare("UPDATE tl_comments SET tstamp=$time, published='" . ($blnVisible ? '1' : '') . "' WHERE id=?")
					   ->execute($intId);

		if ($dc)
		{
			$dc->activeRecord->tstamp = $time;
			$dc->activeRecord->published = ($blnVisible ? '1' : '');
		}

		// Trigger the onsubmit_callback
		if (is_array($GLOBALS['TL_DCA']['tl_comments']['config']['onsubmit_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA']['tl_comments']['config']['onsubmit_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		$objVersions->create();

		if ($dc)
		{
			$dc->invalidateCacheTags();
		}
	}

	/**
	 * Adds the cache invalidation tags for the source.
	 *
	 * @param DataContainer $dc
	 * @param array         $tags
	 *
	 * @return array
	 */
	public function invalidateSourceCacheTags(DataContainer $dc, array $tags)
	{
		$commentModel = CommentsModel::findByPk($dc->id);

		if (null !== $commentModel)
		{
			Controller::loadDataContainer($commentModel->source);

			$tags[] = sprintf('contao.db.%s.%s', $commentModel->source, $commentModel->parent);

			$dc->addPtableTags($commentModel->source, $commentModel->parent, $tags);
		}

		return $tags;
	}
}
