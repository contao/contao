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

$GLOBALS['TL_DCA']['tl_webhook_ingoing'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'enableVersioning' => true,
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
				'token' => 'unique',
				'receiver,enabled' => 'index',
			)
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
			'fields' => array('name', 'receiver'),
			'showColumns' => true,
		),
	),
	'palettes' => array(
		'default' => 'name,receiver,webhookUrl,secret,enabled'
	),
	'fields' => array(
		'id' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'autoincrement' => true)
		),
		'tstamp' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0)
		),
		'token' => array(
			'default' => static fn () => rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='),
			'eval' => array('doNotCopy' => true, 'readonly' => true),
			'sql' => array('type' => 'string', 'length' => 64, 'default' => '')
		),
		'webhookUrl' => array(
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'doNotSave' => true, 'tl_class' => 'long')
		),
		'name' => array(
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => '')
		),
		'receiver' => array(
			'inputType' => 'select',
			'eval' => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'),
			'sql' => array('type' => 'string', 'length' => 190, 'default' => '')
		),
		'secret' => array(
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'decodeEntities' => true, 'hideInput' => true),
			'sql' => array('type' => 'text', 'default' => '')
		),
		'enabled' => array(
			'inputType' => 'checkbox',
			'toggle' => true,
			'filter' => true,
			'sql' => array('type' => 'boolean', 'default' => false)
		),
	),
);
