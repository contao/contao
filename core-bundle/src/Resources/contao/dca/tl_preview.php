<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_preview'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'notCreatable'                => true,
		'onload_callback' => [function () {
			if ('create' === Input::get('act') && Input::get('url')) {
				$GLOBALS['TL_DCA']['tl_preview']['config']['notCreatable'] = false;
				$GLOBALS['TL_DCA']['tl_preview']['fields']['url']['default'] = Input::get('url');
				$GLOBALS['TL_DCA']['tl_preview']['fields']['dateAdded']['default'] = time();
			}
		}],
		'onsubmit_callback' => array
		(
			function (DataContainer $dc) {
				Database::getInstance()->prepare(
					"UPDATE tl_preview SET validUntil=UNIX_TIMESTAMP(DATE_ADD(FROM_UNIXTIME(dateAdded), INTERVAL expires DAY)) WHERE id=?"
				)->execute($dc->id);
			}
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'id,published,validUntil' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('dateAdded'),
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('url', 'showUnpublished', 'validUntil'),
			'showColumns'             => true,
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'share' => array
			(
				'icon'                => 'edit.svg',
				'button_callback' => static function (array $row) {
					/** @var \Symfony\Component\DependencyInjection\Container $container */
					$container = System::getContainer();
					$link = $container->get('router')->generate('contao_frontend_preview', ['id' => $row['id']], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
					$link = $container->get('uri_signer')->sign($link);

					return '<a href="'.$link.'">Link</a>';
				},
			),
			'edit' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				//'button_callback'     => array('tl_member', 'toggleIcon')
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{url_legend},url,showUnpublished;{expire_legend},expires,dateAdded,validUntil;{publishing_legend},published',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'url' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'disabled'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048),
			'sql'                     => "varchar(2048) NOT NULL default ''",
		),
		'showUnpublished' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'expires' => array
		(
			'inputType'               => 'select',
			'options'                 => ['1', '7', '30'],
			'reference'               => &$GLOBALS['TL_LANG']['tl_preview']['expire_options'],
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "char(2) NOT NULL default ''"
		),
		'dateAdded' => array
		(
			'default'                 => time(),
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'disabled'=>true, 'doNotCopy'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'validUntil' => array
		(
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'disabled'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'published' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);
