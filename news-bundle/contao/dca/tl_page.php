<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_page']['palettes']['news_feed'] = '{title_legend},title,type;{routing_legend},alias;{archives_legend},newsArchives;{feed_legend},feedFormat,feedSource,maxFeedItems,feedFeatured,feedDescription;{image_legend},imgSize;{cache_legend:hide},includeCache;{expert_legend:hide},cssClass,sitemap,hide,noSearch;{publish_legend},published,start,stop';

$GLOBALS['TL_DCA']['tl_page']['fields']['newsArchives'] = array(
	'exclude' => true,
	'search' => true,
	'inputType' => 'checkbox',
	'eval' => array('multiple' => true, 'mandatory' => true),
	'sql' => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedFormat'] = array(
	'exclude' => true,
	'filter' => true,
	'inputType' => 'select',
	'options' => array('rss' => 'RSS 2.0', 'atom' => 'Atom', 'json' => 'JSON'),
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(32) NOT NULL default 'rss'"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedSource'] = array(
	'exclude' => true,
	'inputType' => 'select',
	'options' => array('source_teaser', 'source_text'),
	'reference' => &$GLOBALS['TL_LANG']['tl_page'],
	'eval' => array('tl_class' => 'w50'),
	'sql' => "varchar(32) NOT NULL default 'source_teaser'"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['maxFeedItems'] = array(
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('mandatory' => true, 'rgxp' => 'natural', 'tl_class' => 'w50'),
	'sql' => "smallint(5) unsigned NOT NULL default 25"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedFeatured'] = array(
	'exclude' => true,
	'inputType' => 'select',
	'options' => array('all_items', 'featured', 'unfeatured'),
	'reference' => &$GLOBALS['TL_LANG']['tl_page'],
	'eval' => array('tl_class'=>'w50'),
	'sql' => "varchar(16) COLLATE ascii_bin NOT NULL default 'all_items'"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedDescription'] = array(
	'exclude' => true,
	'inputType' => 'textarea',
	'eval' => array('style'=>'height:60px', 'tl_class'=>'clr'),
	'sql' => "text NULL"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['imgSize'] = array(
	'label' => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
	'exclude' => true,
	'inputType' => 'imageSize',
	'reference' => &$GLOBALS['TL_LANG']['MSC'],
	'eval' => array('rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'),
	'options_callback' => array('contao.listener.image_size_options', '__invoke'),
	'sql' => "varchar(255) NOT NULL default ''"
);
