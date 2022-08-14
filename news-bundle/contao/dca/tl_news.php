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
use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\LayoutModel;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\System;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_news'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_news_archive',
		'ctable'                      => array('tl_content'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'headline',
		'onload_callback' => array
		(
			array('tl_news', 'checkPermission'),
		),
		'onsubmit_callback' => array
		(
			array('tl_news', 'adjustTime'),
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_news', 'addSitemapCacheInvalidationTag'),
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
			'fields'                  => array('date'),
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'protected', 'allowComments'),
			'panelLayout'             => 'filter;sort,search,limit',
		),
		'label' => array
		(
			'fields' => array('headline', 'date', 'time'),
			'format' => '%s <span style="color:#999;padding-left:3px">[%s %s]</span>',
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
			'edit',
			'children',
			'copy',
			'cut',
			'delete',
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
			'show'
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('source', 'addImage', 'addEnclosure', 'overwriteMeta'),
		'default'                     => '{title_legend},headline,featured,alias,author;{date_legend},date,time;{source_legend},source,linkText;{meta_legend},pageTitle,robots,description,serpPreview;{teaser_legend},subheadline,teaser;{image_legend},addImage;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop',
		'internal'                    => '{title_legend},headline,featured,alias,author;{date_legend},date,time;{source_legend},source,jumpTo,linkText;{teaser_legend},subheadline,teaser;{image_legend},addImage;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop',
		'article'                     => '{title_legend},headline,featured,alias,author;{date_legend},date,time;{source_legend},source,articleId,linkText;{teaser_legend},subheadline,teaser;{image_legend},addImage;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop',
		'external'                    => '{title_legend},headline,featured,alias,author;{date_legend},date,time;{source_legend},source,url,target,linkText;{teaser_legend},subheadline,teaser;{image_legend},addImage;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},cssClass,noComments;{publish_legend},published,start,stop'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'addImage'                    => 'singleSRC,fullsize,size,floating,overwriteMeta',
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
			'foreignKey'              => 'tl_news_archive.title',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'headline' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'featured' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'alias' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_news', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'author' => array
		(
			'default'                 => BackendUser::getInstance()->id,
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
		'date' => array
		(
			'default'                 => time(),
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'mandatory'=>true, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'load_callback' => array
			(
				array('tl_news', 'loadDate')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'time' => array
		(
			'default'                 => time(),
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'time', 'mandatory'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_news', 'loadTime')
			),
			'sql'                     => "int(10) NOT NULL default 0"
		),
		'pageTitle' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'robots' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'),
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption' => true),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'description' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'serpPreview' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
			'inputType'               => 'serpPreview',
			'eval'                    => array('url_callback'=>array('tl_news', 'getSerpUrl'), 'title_tag_callback'=>array('tl_news', 'getTitleTag'), 'titleFields'=>array('pageTitle', 'headline'), 'descriptionFields'=>array('description', 'teaser')),
			'sql'                     => null
		),
		'subheadline' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'teaser' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'addImage' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'overwriteMeta' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>'%contao.image.valid_extensions%', 'mandatory'=>true),
			'sql'                     => "binary(16) NULL"
		),
		'alt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 clr'),
			'options_callback' => static function ()
			{
				return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'imageUrl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'fullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'caption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'floating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(12) NOT NULL default 'above'"
		),
		'addEnclosure' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'enclosure' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'isDownloads'=>true, 'extensions'=>Config::get('allowedDownload'), 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'source' => array
		(
			'filter'                  => true,
			'inputType'               => 'radio',
			'options_callback'        => array('tl_news', 'getSourceOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_news'],
			'eval'                    => array('submitOnChange'=>true, 'helpwizard'=>true),
			'sql'                     => "varchar(12) NOT NULL default 'default'"
		),
		'linkText' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'articleId' => array
		(
			'inputType'               => 'select',
			'options_callback'        => array('tl_news', 'getArticleAlias'),
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('table'=>'tl_article', 'type'=>'hasOne', 'load'=>'lazy'),
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'target' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'cssClass' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'noComments' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'published' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'start' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property News $News
 *
 * @internal
 */
class tl_news extends Backend
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
	 * Check permissions to edit table tl_news
	 *
	 * @param DataContainer $dc
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission(DataContainer $dc)
	{
		$bundles = System::getContainer()->getParameter('kernel.bundles');

		// HOOK: comments extension required
		if (!isset($bundles['ContaoCommentsBundle']))
		{
			$key = array_search('allowComments', $GLOBALS['TL_DCA']['tl_news']['list']['sorting']['headerFields'] ?? array());
			unset($GLOBALS['TL_DCA']['tl_news']['list']['sorting']['headerFields'][$key], $GLOBALS['TL_DCA']['tl_news']['fields']['noComments']);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set the root IDs
		if (empty($this->User->news) || !is_array($this->User->news))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->news;
		}

		$id = strlen(Input::get('id')) ? Input::get('id') : $dc->currentPid;

		// Check current action
		switch (Input::get('act'))
		{
			case 'paste':
			case 'select':
				// Check currentPid here (see #247)
				if (!in_array($dc->currentPid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access news archive ID ' . $id . '.');
				}
				break;

			case 'create':
				if (!Input::get('pid') || !in_array(Input::get('pid'), $root))
				{
					throw new AccessDeniedException('Not enough permissions to create news items in news archive ID ' . Input::get('pid') . '.');
				}
				break;

			case 'cut':
			case 'copy':
				if (Input::get('act') == 'cut' && Input::get('mode') == 1)
				{
					$objArchive = $this->Database->prepare("SELECT pid FROM tl_news WHERE id=?")
												 ->limit(1)
												 ->execute(Input::get('pid'));

					if ($objArchive->numRows < 1)
					{
						throw new AccessDeniedException('Invalid news item ID ' . Input::get('pid') . '.');
					}

					$pid = $objArchive->pid;
				}
				else
				{
					$pid = Input::get('pid');
				}

				if (!in_array($pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' news item ID ' . $id . ' to news archive ID ' . $pid . '.');
				}
				// no break

			case 'edit':
			case 'show':
			case 'delete':
			case 'toggle':
				$objArchive = $this->Database->prepare("SELECT pid FROM tl_news WHERE id=?")
											 ->limit(1)
											 ->execute($id);

				if ($objArchive->numRows < 1)
				{
					throw new AccessDeniedException('Invalid news item ID ' . $id . '.');
				}

				if (!in_array($objArchive->pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' news item ID ' . $id . ' of news archive ID ' . $objArchive->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access news archive ID ' . $id . '.');
				}

				$objArchive = $this->Database->prepare("SELECT id FROM tl_news WHERE pid=?")
											 ->execute($id);

				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access news archive ID ' . $id . '.');
				}
				break;
		}
	}

	/**
	 * Auto-generate the news alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = function (string $alias) use ($dc): bool
		{
			return $this->Database->prepare("SELECT id FROM tl_news WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
		};

		// Generate alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->headline, NewsArchiveModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
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
	 * Set the timestamp to 00:00:00 (see #26)
	 *
	 * @param integer $value
	 *
	 * @return integer
	 */
	public function loadDate($value)
	{
		return strtotime(date('Y-m-d', $value) . ' 00:00:00');
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
	 * Return the SERP URL
	 *
	 * @param NewsModel $model
	 *
	 * @return string
	 */
	public function getSerpUrl(NewsModel $model)
	{
		return News::generateNewsUrl($model, false, true);
	}

	/**
	 * Return the title tag from the associated page layout
	 *
	 * @param NewsModel $model
	 *
	 * @return string
	 */
	public function getTitleTag(NewsModel $model)
	{
		/** @var NewsArchiveModel $archive */
		if (!$archive = $model->getRelated('pid'))
		{
			return '';
		}

		/** @var PageModel $page */
		if (!$page = $archive->getRelated('jumpTo'))
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

			$objAlias = $this->Database->execute("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") ORDER BY parent, a.sorting");
		}
		else
		{
			$objAlias = $this->Database->execute("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting");
		}

		if ($objAlias->numRows)
		{
			System::loadLanguageFile('tl_article');

			while ($objAlias->next())
			{
				$arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?: $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
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

		$security = System::getContainer()->get('security.helper');
		$arrOptions = array('default');

		// Add the "internal" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_news::jumpTo'))
		{
			$arrOptions[] = 'internal';
		}

		// Add the "article" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_news::articleId'))
		{
			$arrOptions[] = 'article';
		}

		// Add the "external" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_news::url'))
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
		// Return if there is no active record (override all)
		if (!$dc->activeRecord)
		{
			return;
		}

		$arrSet['date'] = strtotime(date('Y-m-d', $dc->activeRecord->date) . ' ' . date('H:i:s', $dc->activeRecord->time));
		$arrSet['time'] = $arrSet['date'];

		$this->Database->prepare("UPDATE tl_news %s WHERE id=?")->set($arrSet)->execute($dc->id);
	}

	/**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$archiveModel = NewsArchiveModel::findByPk($dc->activeRecord->pid);
		$pageModel = PageModel::findWithDetails($archiveModel->jumpTo);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}
}
