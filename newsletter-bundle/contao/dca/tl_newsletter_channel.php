<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_newsletter_channel'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_newsletter', 'tl_newsletter_recipients'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'userRoot'                    => 'newsletters',
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		),
		'operations' => array
		(
			'-',
			'recipients' => array
			(
				'href'                => 'table=tl_newsletter_recipients',
				'icon'                => 'mgroup.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},title,jumpTo;{template_legend:hide},template;{sender_legend},sender,senderName,mailerTransport'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'tstamp' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'title' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'jumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0),
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'template' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'options_callback' => static function () {
				return Controller::getTemplateGroup('mail_');
			},
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'mailerTransport' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w33'),
			'options_callback'        => array('contao.mailer.available_transports', 'getTransportOptions'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'sender' => array
		(
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w33'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'senderName' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w33'),
			'sql'                     => array('type'=>'string', 'length'=>128, 'default'=>'')
		)
	)
);
