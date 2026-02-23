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
use Contao\Comments;
use Contao\CommentsBundle\Security\ContaoCommentsPermissions;
use Contao\CommentsModel;
use Contao\CommentsNotifyModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\Email;
use Contao\Environment;
use Contao\Idna;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_comments'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'closed'                      => true,
		'notCopyable'                 => true,
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
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('date'),
			'panelLayout'             => 'search,filter,sort,limit',
			'defaultSearchField'      => 'comment',
			'limitHeight'             => 104
		),
		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s',
			'label_callback'          => array('tl_comments', 'listComments')
		),
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('addReply'),
		'default'                     => '{author_legend},name,member,email,website;{comment_legend},comment;{reply_legend},addReply;{publish_legend},published'
	),

	// Sub-palettes
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
			'reference'               => &$GLOBALS['TL_LANG']['tl_comments'],
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'parent' => array
		(
			'filter'                  => true,
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
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'email' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'rgxp'=>'email', 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'website' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>128, 'rgxp'=>HttpUrlListener::RGXP_NAME, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'member' => array
		(
			'inputType'               => 'select',
			'foreignKey'              => 'tl_member.CONCAT(firstname," ",lastname)',
			'eval'                    => array('chosen'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'comment' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'rte'=>'tinyMCE'),
			'sql'                     => "text NULL"
		),
		'addReply' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'author' => array
		(
			'default'                 => static fn () => BackendUser::getInstance()->id,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('mandatory'=>true, 'chosen'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'reply' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'published' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'sorting'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'save_callback' => array
			(
				array('tl_comments', 'sendNotifications')
			),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'ip' => array
		(
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'notified' => array
		(
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'notifiedReply' => array
		(
			'sql'                     => array('type' => 'boolean', 'default' => false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_comments extends Backend
{
	/**
	 * Notify subscribers of a reply
	 *
	 * @param DataContainer $dc
	 */
	public function notifyOfReply(DataContainer $dc)
	{
		// Return if there is no active record (override all) or no reply or the notification has been sent already
		if (!$dc->activeRecord?->addReply || $dc->activeRecord->notifiedReply)
		{
			return;
		}

		$objNotify = CommentsNotifyModel::findActiveBySourceAndParent($dc->activeRecord->source, $dc->activeRecord->parent);

		if ($objNotify !== null)
		{
			$baseUrl = Idna::decode(Environment::get('base'));

			while ($objNotify->next())
			{
				// Prepare the URL
				$strUrl = UrlUtil::makeAbsolute($objNotify->url, $baseUrl);

				$objEmail = new Email();
				$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'] ?? null;
				$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'] ?? null;
				$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['com_notifyReplySubject'], Idna::decode(Environment::get('host')));
				$objEmail->text = sprintf($GLOBALS['TL_LANG']['MSC']['com_notifyReplyMessage'], $objNotify->name, $strUrl . '#c' . $dc->id, $strUrl . '?token=' . $objNotify->tokenRemove);
				$objEmail->sendTo($objNotify->email);
			}
		}

		Database::getInstance()->prepare("UPDATE tl_comments SET notifiedReply=1 WHERE id=?")->execute($dc->id);
	}

	/**
	 * Check whether the user is allowed to edit a comment
	 *
	 * @param integer $intParent
	 * @param string  $strSource
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
	 *             vote on the ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT security attribute instead
	 */
	protected function isAllowedToEditComment($intParent, $strSource)
	{
		trigger_deprecation('contao/comments-bundle', '5.6', 'Using "%s()" is deprecated and will no longer work in Contao 6. Vote on the %s::USER_CAN_ACCESS_COMMENT security attribute instead.', __METHOD__, ContaoCommentsPermissions::class);

		return System::getContainer()->get('security.helper')->isGranted(ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT, array('source' => $strSource, 'parent' => $intParent));
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
		if ($varValue && ($id = Input::get('id')))
		{
			Comments::notifyCommentsSubscribers(CommentsModel::findById($id));
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
		$router = System::getContainer()->get('router');
		$title = $GLOBALS['TL_LANG']['tl_comments'][$arrRow['source']] . ' ' . $arrRow['parent'];
		$onClick = ' onclick="Backend.openModalIframe({ title: \'&nbsp;\', url: this.href + \'&amp;popup=1&amp;nb=1\' }); return false;"';

		switch ($arrRow['source'])
		{
			case 'tl_content':
				$objParent = Database::getInstance()
					->prepare("SELECT id, title FROM tl_article WHERE id=(SELECT pid FROM tl_content WHERE id=?)")
					->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="' . StringUtil::specialcharsUrl($router->generate('contao_backend', array('do'=>'article', 'table'=>'tl_content', 'id'=>$objParent->id))) . '"' . $onClick . '>' . $objParent->title . '</a>';
				}
				break;

			case 'tl_page':
				$objParent = Database::getInstance()
					->prepare("SELECT id, title FROM tl_page WHERE id=?")
					->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="' . StringUtil::specialcharsUrl($router->generate('contao_backend', array('do'=>'page', 'act'=>'edit', 'id'=>$objParent->id))) . '"' . $onClick . '>' . $objParent->title . '</a>';
				}
				break;

			case 'tl_news':
				$objParent = Database::getInstance()
					->prepare("SELECT id, headline FROM tl_news WHERE id=?")
					->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="' . StringUtil::specialcharsUrl($router->generate('contao_backend', array('do'=>'news', 'table'=>'tl_news', 'act'=>'edit', 'id'=>$objParent->id))) . '"' . $onClick . '>' . $objParent->headline . '</a>';
				}
				break;

			case 'tl_faq':
				$objParent = Database::getInstance()
					->prepare("SELECT id, question FROM tl_faq WHERE id=?")
					->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="' . StringUtil::specialcharsUrl($router->generate('contao_backend', array('do'=>'faq', 'table'=>'tl_faq', 'act'=>'edit', 'id'=>$objParent->id))) . '"' . $onClick . '>' . $objParent->question . '</a>';
				}
				break;

			case 'tl_calendar_events':
				$objParent = Database::getInstance()
					->prepare("SELECT id, title FROM tl_calendar_events WHERE id=?")
					->execute($arrRow['parent']);

				if ($objParent->numRows)
				{
					$title .= ' – <a href="' . StringUtil::specialcharsUrl($router->generate('contao_backend', array('do'=>'calendar', 'table'=>'tl_calendar_events', 'act'=>'edit', 'id'=>$objParent->id))) . '"' . $onClick . '>' . $objParent->title . '</a>';
				}
				break;

			default:
				// HOOK: support custom modules
				if (isset($GLOBALS['TL_HOOKS']['listComments']) && is_array($GLOBALS['TL_HOOKS']['listComments']))
				{
					foreach ($GLOBALS['TL_HOOKS']['listComments'] as $callback)
					{
						if ($tmp = System::importStatic($callback[0])->{$callback[1]}($arrRow))
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
<div class="cte_type ' . $key . '"><a href="mailto:' . Idna::decodeEmail($arrRow['email']) . '" title="' . StringUtil::specialchars(Idna::decodeEmail($arrRow['email'])) . '">' . $arrRow['name'] . '</a>' . ($arrRow['website'] ? ' (<a href="' . $arrRow['website'] . '" title="' . StringUtil::specialchars($arrRow['website']) . '" target="_blank" rel="noreferrer noopener">' . $GLOBALS['TL_LANG']['MSC']['com_website'] . '</a>)' : '') . ' – ' . Date::parse(Config::get('datimFormat'), $arrRow['date']) . ' – IP ' . StringUtil::specialchars($arrRow['ip']) . '<br>' . $title . '</div>
<div class="cte_preview">
' . $arrRow['comment'] . '
</div>' . "\n    ";
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
		$commentModel = CommentsModel::findById($dc->id);

		if (null !== $commentModel)
		{
			Controller::loadDataContainer($commentModel->source);

			$tags[] = sprintf('contao.db.%s.%s', $commentModel->source, $commentModel->parent);

			$dc->addPtableTags($commentModel->source, $commentModel->parent, $tags);
		}

		return $tags;
	}
}
