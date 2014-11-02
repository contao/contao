<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package News
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

// Classes
ClassLoader::addClasses(array
(
	// Classes
	'Contao\News'              => 'vendor/contao/news-bundle/contao/classes/News.php',

	// Models
	'Contao\NewsArchiveModel'  => 'vendor/contao/news-bundle/contao/models/NewsArchiveModel.php',
	'Contao\NewsFeedModel'     => 'vendor/contao/news-bundle/contao/models/NewsFeedModel.php',
	'Contao\NewsModel'         => 'vendor/contao/news-bundle/contao/models/NewsModel.php',

	// Modules
	'Contao\ModuleNews'        => 'vendor/contao/news-bundle/contao/modules/ModuleNews.php',
	'Contao\ModuleNewsArchive' => 'vendor/contao/news-bundle/contao/modules/ModuleNewsArchive.php',
	'Contao\ModuleNewsList'    => 'vendor/contao/news-bundle/contao/modules/ModuleNewsList.php',
	'Contao\ModuleNewsMenu'    => 'vendor/contao/news-bundle/contao/modules/ModuleNewsMenu.php',
	'Contao\ModuleNewsReader'  => 'vendor/contao/news-bundle/contao/modules/ModuleNewsReader.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'mod_newsarchive'   => 'vendor/contao/news-bundle/contao/templates/modules',
	'mod_newslist'      => 'vendor/contao/news-bundle/contao/templates/modules',
	'mod_newsmenu'      => 'vendor/contao/news-bundle/contao/templates/modules',
	'mod_newsmenu_day'  => 'vendor/contao/news-bundle/contao/templates/modules',
	'mod_newsmenu_year' => 'vendor/contao/news-bundle/contao/templates/modules',
	'mod_newsreader'    => 'vendor/contao/news-bundle/contao/templates/modules',
	'news_full'         => 'vendor/contao/news-bundle/contao/templates/news',
	'news_latest'       => 'vendor/contao/news-bundle/contao/templates/news',
	'news_short'        => 'vendor/contao/news-bundle/contao/templates/news',
	'news_simple'       => 'vendor/contao/news-bundle/contao/templates/news',
));
