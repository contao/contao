<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\MySQLPlatform;

$GLOBALS['TL_DCA']['tl_oauth_client'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'enableVersioning' => true,
		'switchToEdit' => true,
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
			),
		),
	),
	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_SORTED,
			'fields' => array('title'),
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout' => 'filter;sort,search,limit'
		),
		'label' => array(
			'fields' => array('title'),
			'format' => '%s',
		),
		'operations' => array(
			'edit',
			'copy',
			'delete',
			'show',
		),
	),
	'fields' => array(
		'id' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'autoincrement' => true),
		),
		'tstamp' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'title' => array(
			'search' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => true),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
		'type' => array(
			'filter' => true,
			'inputType' => 'select',
			'eval' => array('tl_class' => 'w50', 'includeBlankOption' => true, 'mandatory' => true, 'chosen' => true),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
		'client_id' => array(
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => true),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
		'client_secret' => array(
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => true),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
		'scopes' => array(
			'inputType' => 'listWizard',
			'eval' => array('tl_class' => 'clr w50'),
			'sql' => array('type' => 'blob', 'length' => MySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false)
		),
		'graph_api_version' => array(
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => true),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
	),
	'palettes' => array(
		'__selector__' => array('type'),
		'default' => '{oauth_legend},title,type;{config_legend},client_id,client_secret,scopes',
		'facebook' => '{oauth_legend},title,type;{config_legend},client_id,client_secret,graph_api_version,scopes',
	),
);
