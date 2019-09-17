<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Back end modules
array_insert($GLOBALS['BE_MOD']['content'], 1, array
(
	'news' => array
	(
		'tables'      => array('tl_news_archive', 'tl_news', 'tl_news_feed', 'tl_content'),
		'table'       => array('Contao\TableWizard', 'importTable'),
		'list'        => array('Contao\ListWizard', 'importList')
	)
));

// Front end modules
array_insert($GLOBALS['FE_MOD'], 2, array
(
	'news' => array
	(
		'newslist'    => 'Contao\ModuleNewsList',
		'newsreader'  => 'Contao\ModuleNewsReader',
		'newsarchive' => 'Contao\ModuleNewsArchive',
		'newsmenu'    => 'Contao\ModuleNewsMenu'
	)
));

// Cron jobs
$GLOBALS['TL_CRON']['daily']['generateNewsFeeds'] = array('Contao\News', 'generateFeeds');

// Style sheet
if (\defined('TL_MODE') && TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaonews/news.min.css|static';
}

// Register hooks
$GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array('Contao\News', 'purgeOldFeeds');
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('Contao\News', 'getSearchablePages');
$GLOBALS['TL_HOOKS']['generateXmlFiles'][] = array('Contao\News', 'generateFeeds');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'news';
$GLOBALS['TL_PERMISSIONS'][] = 'newp';
$GLOBALS['TL_PERMISSIONS'][] = 'newsfeeds';
$GLOBALS['TL_PERMISSIONS'][] = 'newsfeedp';
