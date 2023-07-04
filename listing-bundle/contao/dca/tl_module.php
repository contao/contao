<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\Controller;
use Contao\Database;
use Contao\System;

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['listing'] = '{title_legend},name,headline,type;{config_legend},list_table,list_fields,list_where,list_search,list_sort,perPage,list_info,list_info_where;{template_legend:hide},list_layout,list_info_layout;{protected_legend:hide},protected;{expert_legend:hide},cssID';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['list_table'] = array
(
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_listing', 'getAllTables'),
	'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_fields'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "tinytext NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_where'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('preserveTags'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "tinytext NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_search'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "tinytext NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_sort'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "tinytext NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_info'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "tinytext NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_info_where'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('preserveTags'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "tinytext NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_layout'] = array
(
	'inputType'               => 'select',
	'options_callback' => static function () {
		return Controller::getTemplateGroup('list_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['list_info_layout'] = array
(
	'inputType'               => 'select',
	'options_callback' => static function () {
		return Controller::getTemplateGroup('info_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_module_listing extends Backend
{
	/**
	 * Get all tables and return them as array
	 *
	 * @return array
	 */
	public function getAllTables()
	{
		$arrTables = Database::getInstance()->listTables();
		$arrViews = System::getContainer()->get('database_connection')->createSchemaManager()->listViews();

		if (!empty($arrViews))
		{
			$arrTables = array_merge($arrTables, array_keys($arrViews));
			natsort($arrTables);
		}

		return array_values($arrTables);
	}
}
