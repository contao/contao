<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Back end modules
 */
array_insert($GLOBALS['BE_MOD']['content'], 1, array
(
	'news' => array
	(
		'tables'      => array('tl_news_archive', 'tl_news', 'tl_news_feed', 'tl_content'),
		'icon'        => 'bundles/contaonews/icon.gif',
		'table'       => array('TableWizard', 'importTable'),
		'list'        => array('ListWizard', 'importList')
	)
));


/**
 * Front end modules
 */
array_insert($GLOBALS['FE_MOD'], 2, array
(
	'news' => array
	(
		'newslist'    => 'ModuleNewsList',
		'newsreader'  => 'ModuleNewsReader',
		'newsarchive' => 'ModuleNewsArchive',
		'newsmenu'    => 'ModuleNewsMenu'
	)
));


/**
 * Cron jobs
 */
$GLOBALS['TL_CRON']['daily'][] = array('News', 'generateFeeds');


/**
 * Register hooks
 */
$GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array('News', 'purgeOldFeeds');
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('News', 'getSearchablePages');
$GLOBALS['TL_HOOKS']['generateXmlFiles'][] = array('News', 'generateFeeds');
$GLOBALS['TL_HOOKS']['addFileMetaInformationToRequest'][] = array('contao_news.listener.file_meta_information', 'onAddFileMetaInformationToRequest');


/**
 * Add permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'news';
$GLOBALS['TL_PERMISSIONS'][] = 'newp';
$GLOBALS['TL_PERMISSIONS'][] = 'newsfeeds';
$GLOBALS['TL_PERMISSIONS'][] = 'newsfeedp';
