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

$GLOBALS['TL_DCA']['tl_member_oauth'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_member',
        'closed' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid,oauthClient' => 'unique',
                'oauthClient,oauthId' => 'index',
            ],
        ], 
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['id'],
            'headerFields' => ['firstname', 'lastname', 'username'],
            'disableGrouping' => true,
            'panelLayout' => 'limit',
            'child_record_callback' => function (array $row) {
                $client = OAuthClientModel::findByPk($row['oauthClient']);

                return '<div class="tl_content_left">'.$client->title.': '.$row['oauthId'].'</div>';
            },
        ],
        'operations' => [
            'delete',
            'show',
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'pid' => [
            'foreignKey' => 'tl_member.CONCAT(firstname," ",lastname)',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
			'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'oauthClient' => [
            'foreignKey' => 'tl_oauth_client.title',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'oauthId' => [
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
    ],
];
