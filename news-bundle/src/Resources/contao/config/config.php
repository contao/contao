<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ListWizard;
use Contao\ModuleNewsArchive;
use Contao\ModuleNewsList;
use Contao\ModuleNewsMenu;
use Contao\ModuleNewsReader;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsFeedModel;
use Contao\NewsModel;
use Contao\System;
use Contao\TableWizard;
use Symfony\Component\HttpFoundation\Request;

// Back end modules
$GLOBALS['BE_MOD']['content']['news'] = array
(
	'tables'      => array('tl_news_archive', 'tl_news', 'tl_news_feed', 'tl_content'),
	'table'       => array(TableWizard::class, 'importTable'),
	'list'        => array(ListWizard::class, 'importList')
);

// Front end modules
$GLOBALS['FE_MOD']['news'] = array
(
	'newslist'    => ModuleNewsList::class,
	'newsreader'  => ModuleNewsReader::class,
	'newsarchive' => ModuleNewsArchive::class,
	'newsmenu'    => ModuleNewsMenu::class
);

// Style sheet
if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaonews/news.min.css|static';
}

// Register hooks
$GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array(News::class, 'purgeOldFeeds');
$GLOBALS['TL_HOOKS']['generateXmlFiles'][] = array(News::class, 'generateFeeds');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'news';
$GLOBALS['TL_PERMISSIONS'][] = 'newp';
$GLOBALS['TL_PERMISSIONS'][] = 'newsfeeds';
$GLOBALS['TL_PERMISSIONS'][] = 'newsfeedp';

// Models
$GLOBALS['TL_MODELS']['tl_news_archive'] = NewsArchiveModel::class;
$GLOBALS['TL_MODELS']['tl_news_feed'] = NewsFeedModel::class;
$GLOBALS['TL_MODELS']['tl_news'] = NewsModel::class;
