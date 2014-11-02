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
