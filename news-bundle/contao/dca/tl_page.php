<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Doctrine\DBAL\Platforms\MySQLPlatform;

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
	'sql' => array('type'=>'blob', 'length'=>MySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false)
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedSource'] = array(
	'exclude' => true,
	'inputType' => 'select',
	'options' => array('source_teaser', 'source_text'),
	'reference' => &$GLOBALS['TL_LANG']['tl_page'],
	'eval' => array('tl_class' => 'w50'),
	'sql' => array('type'=>'string', 'length'=>32, 'default'=>'source_teaser', 'customSchemaOptions'=>array('collation'=>'ascii_bin'))
);

$GLOBALS['TL_DCA']['tl_page']['fields']['feedFeatured'] = array(
	'exclude' => true,
	'inputType' => 'select',
	'options' => array('all_items', 'featured', 'unfeatured'),
	'reference' => &$GLOBALS['TL_LANG']['tl_page'],
	'eval' => array('tl_class' => 'w50'),
	'sql' => array('type'=>'string', 'length'=>16, 'default'=>'all_items', 'customSchemaOptions'=>array('collation'=>'ascii_bin'))
);
