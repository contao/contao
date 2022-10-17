<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_url_rewrite'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['name'],
            'flag' => 1,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['type', 'responseCode'],
        'default' => '{name_legend},name,type,priority,comment,disable',
        'basic' => '{name_legend},name,type,priority,comment,disable;{request_legend},requestHost,requestPath,requestRequirements;{response_legend},responseCode;{examples_legend},examples',
        'expert' => '{name_legend},name,type,priority,comment,disable;{request_legend},requestHost,requestPath,requestCondition;{response_legend},responseCode;{examples_legend},examples',
    ],

    // Subpalettes
    'subpalettes' => [
        'responseCode_301' => 'responseUri',
        'responseCode_302' => 'responseUri',
        'responseCode_303' => 'responseUri',
        'responseCode_307' => 'responseUri',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'name' => [
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'flag' => 1,
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'type' => [
            'default' => 'basic',
            'filter' => true,
            'inputType' => 'select',
            'options' => ['basic', 'expert'],
            'reference' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['typeRef'],
            'eval' => ['submitOnChange' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'priority' => [
            'default' => '0',
            'filter' => true,
            'sorting' => true,
            'flag' => 12,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'default' => '0'],
            'save_callback' => [static function ($value) {
                if (!preg_match('/^-?\d+$/', $value)) {
                    throw new \RuntimeException($GLOBALS['TL_LANG']['ERR']['digit']);
                }

                return $value;
            }]
        ],
        'comment' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'disable' => [
			'reverseToggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'boolean', 'default' => 0],
        ],
        'requestHost' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'requestPath' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'requestRequirements' => [
            'inputType' => 'keyValueWizard',
            'eval' => ['decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
        ],
        'requestCondition' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'string', 'default' => ''],
        ],
        'responseCode' => [
            'default' => 301,
            'filter' => true,
            'sorting' => true,
            'flag' => 11,
            'inputType' => 'select',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true],
        ],
        'responseUri' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => [
                'decodeEntities' => true,
                'dcaPicker' => true,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'tl_class' => 'clr wizard',
            ],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'examples' => [
            // input_field_callback from RewriteExamplesListener
        ],
    ],
];
