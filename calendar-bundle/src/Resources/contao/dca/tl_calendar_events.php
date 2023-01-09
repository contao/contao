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
use Contao\Calendar;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\Events;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_calendar_events'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_calendar',
		'ctable'                      => array('tl_content'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_calendar_events', 'checkPermission'),
			array('tl_calendar_events', 'generateFeed')
		),
		'oncut_callback' => array
		(
			array('tl_calendar_events', 'scheduleUpdate')
		),
		'ondelete_callback' => array
		(
			array('tl_calendar_events', 'scheduleUpdate')
		),
		'onsubmit_callback' => array
		(
			array('tl_calendar_events', 'adjustTime'),
			array('tl_calendar_events', 'scheduleUpdate')
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_calendar_events', 'addSitemapCacheInvalidationTag'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'alias' => 'index',
				'pid,published,featured,start,stop' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('startTime'),
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'protected', 'allowComments'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_calendar_events', 'listEvents')
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
				'href'                => 'table=tl_content',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'header.svg'
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'showInHeader'        => true
			),
			'feature' => array
			(
				'href'                => 'act=toggle&amp;field=featured',
				'icon'                => 'featured.svg',
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
		'__selector__'                => array('source', 'addTime', 'addImage', 'recurring', 'addEnclosure', 'overwriteMeta'),
		'default'                     => '{title_legend},title,featured,alias,author;{date_legend},addTime,startDate,endDate;{source_legend:hide},source;{meta_legend},pageTitle,robots,description,serpPreview;{details_legend},location,address,teaser;{image_legend},addImage;{recurring_legend},recurring;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop',
		'internal'                    => '{title_legend},title,featured,alias,author;{date_legend},addTime,startDate,endDate;{source_legend},source,jumpTo;{details_legend},location,address,teaser;{image_legend},addImage;{recurring_legend},recurring;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop',
		'article'                     => '{title_legend},title,featured,alias,author;{date_legend},addTime,startDate,endDate;{source_legend},source,articleId;{details_legend},location,address,teaser;{image_legend},addImage;{recurring_legend},recurring;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop',
		'external'                    => '{title_legend},title,featured,alias,author;{date_legend},addTime,startDate,endDate;{source_legend},source,url,target;{details_legend},location,address,teaser;{image_legend},addImage;{recurring_legend},recurring;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'addTime'                     => 'startTime,endTime',
		'addImage'                    => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
		'recurring'                   => 'repeatEach,recurrences',
		'addEnclosure'                => 'enclosure',
		'overwriteMeta'               => 'alt,imageTitle,imageUrl,caption'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_calendar.title',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'title' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'featured' => array
		(
			'exclude'                 => true,
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'alias' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_calendar_events', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'author' => array
		(
			'default'                 => BackendUser::getInstance()->id,
			'exclude'                 => true,
			'search'                  => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('doNotCopy'=>true, 'chosen'=>true, 'mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'addTime' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'startTime' => array
		(
			'default'                 => time(),
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'time', 'mandatory'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_calendar_events', 'loadTime')
			),
			'sql'                     => "int(10) NULL"
		),
		'endTime' => array
		(
			'default'                 => time(),
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'time', 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_calendar_events', 'loadEndTime')
			),
			'save_callback' => array
			(
				array('tl_calendar_events', 'setEmptyEndTime')
			),
			'sql'                     => "int(10) NULL"
		),
		'startDate' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'mandatory'=>true, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NULL"
		),
		'endDate' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NULL"
		),
		'pageTitle' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'robots' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'),
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption' => true),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'description' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'serpPreview' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
			'exclude'                 => true,
			'inputType'               => 'serpPreview',
			'eval'                    => array('url_callback'=>array('tl_calendar_events', 'getSerpUrl'), 'title_tag_callback'=>array('tl_calendar_events', 'getTitleTag'), 'titleFields'=>array('pageTitle', 'title'), 'descriptionFields'=>array('description', 'teaser')),
			'sql'                     => null
		),
		'location' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'address' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'teaser' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'addImage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'overwriteMeta' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'%contao.image.valid_extensions%', 'mandatory'=>true),
			'sql'                     => "binary(16) NULL"
		),
		'alt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'options_callback' => static function ()
			{
				return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'imagemargin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
			'exclude'                 => true,
			'inputType'               => 'trbl',
			'options'                 => array('px', '%', 'em', 'rem'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'imageUrl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'fullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'caption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'floating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
			'exclude'                 => true,
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(32) NOT NULL default 'above'"
		),
		'recurring' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'repeatEach' => array
		(
			'exclude'                 => true,
			'inputType'               => 'timePeriod',
			'options'                 => array('days', 'weeks', 'months', 'years'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_calendar_events'],
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'minval'=>1, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'repeatEnd' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'recurrences' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'addEnclosure' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'enclosure' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'isDownloads'=>true, 'extensions'=>Config::get('allowedDownload'), 'mandatory'=>true, 'isSortable'=>true),
			'sql'                     => "blob NULL"
		),
		'source' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'radio',
			'options_callback'        => array('tl_calendar_events', 'getSourceOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_calendar_events'],
			'eval'                    => array('submitOnChange'=>true, 'helpwizard'=>true),
			'sql'                     => "varchar(32) NOT NULL default 'default'"
		),
		'jumpTo' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'articleId' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_calendar_events', 'getArticleAlias'),
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('table'=>'tl_article', 'type'=>'hasOne', 'load'=>'lazy'),
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'target' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'cssClass' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'noComments' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'published' => array
		(
			'exclude'                 => true,
			'toggle'                  => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_DESC,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'start' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property Calendar $Calendar
 */
class tl_calendar_events extends Backend
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
	 * Check permissions to edit table tl_calendar_events
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		$bundles = System::getContainer()->getParameter('kernel.bundles');

		// HOOK: comments extension required
		if (!isset($bundles['ContaoCommentsBundle']))
		{
			$key = array_search('allowComments', $GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields'] ?? array());
			unset($GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields'][$key], $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['noComments']);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->calendars) || !is_array($this->User->calendars))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->calendars;
		}

		$id = strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

		// Check current action
		switch (Input::get('act'))
		{
			case 'paste':
			case 'select':
				// Check CURRENT_ID here (see #247)
				if (!in_array(CURRENT_ID, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access calendar ID ' . $id . '.');
				}
				break;

			case 'create':
				if (!Input::get('pid') || !in_array(Input::get('pid'), $root))
				{
					throw new AccessDeniedException('Not enough permissions to create events in calendar ID ' . Input::get('pid') . '.');
				}
				break;

			case 'cut':
			case 'copy':
				if (!in_array(Input::get('pid'), $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' event ID ' . $id . ' to calendar ID ' . Input::get('pid') . '.');
				}
				// no break

			case 'edit':
			case 'show':
			case 'delete':
			case 'toggle':
				$objCalendar = $this->Database->prepare("SELECT pid FROM tl_calendar_events WHERE id=?")
											  ->limit(1)
											  ->execute($id);

				if ($objCalendar->numRows < 1)
				{
					throw new AccessDeniedException('Invalid event ID ' . $id . '.');
				}

				if (!in_array($objCalendar->pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' event ID ' . $id . ' of calendar ID ' . $objCalendar->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access calendar ID ' . $id . '.');
				}

				$objCalendar = $this->Database->prepare("SELECT id FROM tl_calendar_events WHERE pid=?")
											  ->execute($id);

				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCalendar->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access calendar ID ' . $id . '.');
				}
				break;
		}
	}

	/**
	 * Auto-generate the event alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = function (string $alias) use ($dc): bool
		{
			return $this->Database->prepare("SELECT id FROM tl_calendar_events WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
		};

		// Generate the alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, CalendarModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
		}
		elseif (preg_match('/^[1-9]\d*$/', $varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
		}
		elseif ($aliasExists($varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}

	/**
	 * Set the timestamp to 1970-01-01 (see #26)
	 *
	 * @param integer $value
	 *
	 * @return integer
	 */
	public function loadTime($value)
	{
		return strtotime('1970-01-01 ' . date('H:i:s', $value));
	}

	/**
	 * Set the end time to an empty string (see #23)
	 *
	 * @param integer       $value
	 * @param DataContainer $dc
	 *
	 * @return integer
	 */
	public function loadEndTime($value, DataContainer $dc)
	{
		$return = strtotime('1970-01-01 ' . date('H:i:s', $value));

		// Return an empty string if the start time is the same as the end time (see #23)
		if ($dc->activeRecord && $return == $dc->activeRecord->startTime)
		{
			return '';
		}

		// Return an empty string if no time has been set yet
		if ($dc->activeRecord && $return - $dc->activeRecord->startTime == 86399)
		{
			return '';
		}

		return strtotime('1970-01-01 ' . date('H:i:s', $value));
	}

	/**
	 * Automatically set the end time if not set
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function setEmptyEndTime($varValue, DataContainer $dc)
	{
		if ($varValue === null)
		{
			$varValue = $dc->activeRecord->startTime;
		}

		return $varValue;
	}

	/**
	 * Return the SERP URL
	 *
	 * @param CalendarEventsModel $model
	 *
	 * @return string
	 */
	public function getSerpUrl(CalendarEventsModel $model)
	{
		return Events::generateEventUrl($model, true);
	}

	/**
	 * Return the title tag from the associated page layout
	 *
	 * @param CalendarEventsModel $model
	 *
	 * @return string
	 */
	public function getTitleTag(CalendarEventsModel $model)
	{
		/** @var CalendarModel $calendar */
		if (!$calendar = $model->getRelated('pid'))
		{
			return '';
		}

		/** @var PageModel $page */
		if (!$page = $calendar->getRelated('jumpTo'))
		{
			return '';
		}

		$page->loadDetails();

		/** @var LayoutModel $layout */
		if (!$layout = $page->getRelated('layout'))
		{
			return '';
		}

		$origObjPage = $GLOBALS['objPage'] ?? null;

		// Override the global page object, so we can replace the insert tags
		$GLOBALS['objPage'] = $page;

		$title = implode(
			'%s',
			array_map(
				static function ($strVal)
				{
					return str_replace('%', '%%', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strVal));
				},
				explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2)
			)
		);

		$GLOBALS['objPage'] = $origObjPage;

		return $title;
	}

	/**
	 * Add the type of input field
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listEvents($arrRow)
	{
		$span = Calendar::calculateSpan($arrRow['startTime'], $arrRow['endTime']);

		if ($span > 0)
		{
			$date = Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['startTime']) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['endTime']);
		}
		elseif ($arrRow['startTime'] == $arrRow['endTime'])
		{
			$date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']) . ($arrRow['addTime'] ? ' ' . Date::parse(Config::get('timeFormat'), $arrRow['startTime']) : '');
		}
		else
		{
			$date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']) . ($arrRow['addTime'] ? ' ' . Date::parse(Config::get('timeFormat'), $arrRow['startTime']) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse(Config::get('timeFormat'), $arrRow['endTime']) : '');
		}

		return '<div class="tl_content_left">' . $arrRow['title'] . ' <span style="color:#999;padding-left:3px">[' . $date . ']</span></div>';
	}

	/**
	 * Get all articles and return them as array
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getArticleAlias(DataContainer $dc)
	{
		$arrPids = array();
		$arrAlias = array();

		if (!$this->User->isAdmin)
		{
			foreach ($this->User->pagemounts as $id)
			{
				$arrPids[] = array($id);
				$arrPids[] = $this->Database->getChildRecords($id, 'tl_page');
			}

			if (!empty($arrPids))
			{
				$arrPids = array_merge(...$arrPids);
			}
			else
			{
				return $arrAlias;
			}

			$objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}
		else
		{
			$objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}

		if ($objAlias->numRows)
		{
			System::loadLanguageFile('tl_article');

			while ($objAlias->next())
			{
				$arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?? $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
			}
		}

		return $arrAlias;
	}

	/**
	 * Add the source options depending on the allowed fields (see #5498)
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getSourceOptions(DataContainer $dc)
	{
		if ($this->User->isAdmin)
		{
			return array('default', 'internal', 'article', 'external');
		}

		$arrOptions = array('default');
		$security = System::getContainer()->get('security.helper');

		// Add the "internal" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_calendar_events::jumpTo'))
		{
			$arrOptions[] = 'internal';
		}

		// Add the "article" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_calendar_events::articleId'))
		{
			$arrOptions[] = 'article';
		}

		// Add the "external" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_calendar_events::url'))
		{
			$arrOptions[] = 'external';
		}

		// Add the option currently set
		if ($dc->activeRecord && $dc->activeRecord->source)
		{
			$arrOptions[] = $dc->activeRecord->source;
			$arrOptions = array_unique($arrOptions);
		}

		return $arrOptions;
	}

	/**
	 * Adjust start end end time of the event based on date, span, startTime and endTime
	 *
	 * @param DataContainer $dc
	 */
	public function adjustTime(DataContainer $dc)
	{
		// Return if there is no active record (override all) or no start date has been set yet
		if (!$dc->activeRecord || !$dc->activeRecord->startDate)
		{
			return;
		}

		$arrSet['startTime'] = $dc->activeRecord->startDate;
		$arrSet['endTime'] = $dc->activeRecord->startDate;

		// Set end date
		if ($dc->activeRecord->endDate)
		{
			if ($dc->activeRecord->endDate > $dc->activeRecord->startDate)
			{
				$arrSet['endDate'] = $dc->activeRecord->endDate;
				$arrSet['endTime'] = $dc->activeRecord->endDate;
			}
			else
			{
				$arrSet['endDate'] = $dc->activeRecord->startDate;
				$arrSet['endTime'] = $dc->activeRecord->startDate;
			}
		}

		// Add time
		if ($dc->activeRecord->addTime)
		{
			$arrSet['startTime'] = strtotime(date('Y-m-d', $arrSet['startTime']) . ' ' . date('H:i:s', $dc->activeRecord->startTime));
			$arrSet['endTime'] = strtotime(date('Y-m-d', $arrSet['endTime']) . ' ' . date('H:i:s', '' !== (string) $dc->activeRecord->endTime ? $dc->activeRecord->endTime : $dc->activeRecord->startTime));
		}

		// Adjust end time of "all day" events
		elseif (($dc->activeRecord->endDate && $arrSet['endDate'] == $arrSet['endTime']) || $arrSet['startTime'] == $arrSet['endTime'])
		{
			$arrSet['endTime'] = (strtotime('+ 1 day', $arrSet['endTime']) - 1);
		}

		$arrSet['repeatEnd'] = 0;

		// Recurring events
		if ($dc->activeRecord->recurring)
		{
			// Unlimited recurrences end on 2106-02-07 07:28:15 (see #4862 and #510)
			if ($dc->activeRecord->recurrences == 0)
			{
				$arrSet['repeatEnd'] = min(4294967295, PHP_INT_MAX);
			}
			else
			{
				$arrRange = StringUtil::deserialize($dc->activeRecord->repeatEach);

				if (isset($arrRange['unit'], $arrRange['value']))
				{
					$arg = $arrRange['value'] * $dc->activeRecord->recurrences;
					$unit = $arrRange['unit'];

					$strtotime = '+ ' . $arg . ' ' . $unit;
					$arrSet['repeatEnd'] = strtotime($strtotime, $arrSet['endTime']);
				}
			}
		}

		$this->Database->prepare("UPDATE tl_calendar_events %s WHERE id=?")->set($arrSet)->execute($dc->id);
	}

	/**
	 * Check for modified calendar feeds and update the XML files if necessary
	 */
	public function generateFeed()
	{
		$objSession = System::getContainer()->get('session');
		$session = $objSession->get('calendar_feed_updater');

		if (empty($session) || !is_array($session))
		{
			return;
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request)
		{
			$origScope = $request->attributes->get('_scope');
			$request->attributes->set('_scope', 'frontend');
		}

		$this->import(Calendar::class, 'Calendar');

		foreach ($session as $id)
		{
			$this->Calendar->generateFeedsByCalendar($id);
		}

		if ($request)
		{
			$request->attributes->set('_scope', $origScope);
		}

		$objSession->set('calendar_feed_updater', null);
	}

	/**
	 * Schedule a calendar feed update
	 *
	 * This method is triggered when a single event or multiple events are
	 * modified (edit/editAll), moved (cut/cutAll) or deleted (delete/deleteAll).
	 * Since duplicated events are unpublished by default, it is not necessary
	 * to schedule updates on copyAll as well.
	 *
	 * @param DataContainer $dc
	 */
	public function scheduleUpdate(DataContainer $dc)
	{
		// Return if there is no ID
		if (!$dc->activeRecord || !$dc->activeRecord->pid || Input::get('act') == 'copy')
		{
			return;
		}

		$objSession = System::getContainer()->get('session');

		// Store the ID in the session
		$session = $objSession->get('calendar_feed_updater');
		$session[] = $dc->activeRecord->pid;
		$objSession->set('calendar_feed_updater', array_unique($session));
	}

	/**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$calendar = CalendarModel::findByPk($dc->activeRecord->pid);
		$pageModel = PageModel::findWithDetails($calendar->jumpTo);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}
}
