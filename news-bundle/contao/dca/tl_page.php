<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_page']['palettes']['news_feed'] = '{title_legend},title,type;{routing_legend},alias,routePath,routePriority,routeConflicts;{archives_legend},newsArchives;{feed_legend},feedFormat,feedSource,maxFeedItems,feedFeatured,feedDescription;{image_legend},imgSize;{cache_legend:hide},includeCache;{expert_legend:hide},cssClass,sitemap,hide;{publish_legend},published,start,stop';

$GLOBALS['TL_DCA']['tl_page']['fields']['newsArchives'] = array(
	'exclude' => true,
	'search' => true,
	'inputType' => 'checkbox',
	'eval' => array('multiple' => true, 'mandatory' => true),
	'sql' => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedSource'] = array(
	'exclude' => true,
	'inputType' => 'select',
	'options' => array('source_teaser', 'source_text'),
	'reference' => &$GLOBALS['TL_LANG']['tl_page'],
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(32) COLLATE ascii_bin NOT NULL default 'source_teaser'"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedFeatured'] = array(
	'exclude' => true,
	'inputType' => 'select',
	'options' => array('all_items', 'featured', 'unfeatured'),
	'reference' => &$GLOBALS['TL_LANG']['tl_page'],
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(16) COLLATE ascii_bin NOT NULL default 'all_items'"
);
