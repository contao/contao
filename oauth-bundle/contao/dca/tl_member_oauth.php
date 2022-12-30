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
use Contao\OAuthBundle\Model\OAuthClientModel;

$GLOBALS['TL_DCA']['tl_member_oauth'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'ptable' => 'tl_member',
		'closed' => true,
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
				'pid,oauthClient' => 'unique',
				'pid,oauthClient,oauthId' => 'unique',
			),
		),
	),
	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_PARENT,
			'fields' => array('id'),
			'headerFields' => array('firstname', 'lastname', 'username'),
			'disableGrouping' => true,
			'panelLayout' => 'limit',
			'child_record_callback' => static function (array $row)
			{
				$client = OAuthClientModel::findByPk($row['oauthClient']);

				return '<div class="tl_content_left">' . $client->title . ': ' . $row['oauthId'] . '</div>';
			},
		),
		'operations' => array(
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
		'pid' => array(
			'foreignKey' => 'tl_member.CONCAT(firstname," ",lastname)',
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
			'relation' => array('type' => 'belongsTo', 'load' => 'lazy'),
		),
		'oauthClient' => array(
			'foreignKey' => 'tl_oauth_client.title',
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
			'relation' => array('type' => 'belongsTo', 'load' => 'lazy'),
		),
		'oauthId' => array(
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
		'oauthUserData' => array(
			'sql' => array('type' => 'json', 'notnull' => false),
		),
	),
);
