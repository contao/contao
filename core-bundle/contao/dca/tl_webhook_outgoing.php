<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

$GLOBALS['TL_DCA']['tl_webhook_outgoing'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'enableVersioning' => true,
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
				'enabled' => 'index',
			),
		),
	),
	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_SORTABLE,
			'fields' => array('name'),
			'panelLayout' => 'search,filter,sort,limit',
			'defaultSearchField' => 'name',
		),
		'label' => array(
			'fields' => array('name', 'url'),
			'showColumns' => true,
		),
	),
	'palettes' => array(
		'default' => 'name,url,events,secret,enabled',
	),
	'fields' => array(
		'id' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'autoincrement' => true),
		),
		'tstamp' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'name' => array(
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
		'url' => array(
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'rgxp' => 'url', 'maxlength' => 2048, 'tl_class' => 'w50'),
			'sql' => array('type' => 'string', 'length' => 2048, 'default' => ''),
		),
		'events' => array(
			'inputType' => 'checkboxWizard',
			'eval' => array('multiple' => true, 'mandatory' => true),
			'sql' => array('type' => 'blob', 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false)
		),
		'secret' => array(
			'eval' => array('tl_class' => 'w50', 'hideInput' => true),
			'sql' => array('type' => 'text', 'default' => ''),
		),
		'enabled' => array(
			'inputType' => 'checkbox',
			'toggle' => true,
			'filter' => true,
			'sql' => array('type' => 'boolean', 'default' => false),
		),
	),
);
