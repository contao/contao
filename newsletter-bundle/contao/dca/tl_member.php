<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Add palette
$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('assignDir;', 'assignDir;{newsletter_legend:hide},newsletter;', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);

// Add load callback
$GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'][] = array('Newsletter', 'updateAccount');

// Add save callback
$GLOBALS['TL_DCA']['tl_member']['fields']['disable']['save_callback'][] = array('Newsletter', 'onToggleVisibility');

// Add field
$GLOBALS['TL_DCA']['tl_member']['fields']['newsletter'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_newsletter_channel.title',
	'options_callback'        => array('Newsletter', 'getNewsletters'),
	'eval'                    => array('multiple'=>true, 'feEditable'=>true, 'feGroup'=>'newsletter'),
	'save_callback' => array
	(
		array('Newsletter', 'synchronize')
	),
	'sql'                     => "blob NULL"
);
