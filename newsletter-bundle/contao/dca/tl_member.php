<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Newsletter;

// Add palette
$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('assignDir;', 'assignDir;{newsletter_legend:hide},newsletter;', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);

// Add load callback
$GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'][] = array(Newsletter::class, 'updateAccount');

// Add save callback
$GLOBALS['TL_DCA']['tl_member']['fields']['disable']['save_callback'][] = array(Newsletter::class, 'onToggleVisibility');

// Add field
$GLOBALS['TL_DCA']['tl_member']['fields']['newsletter'] = array
(
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_newsletter_channel.title',
	'options_callback'        => array(Newsletter::class, 'getNewsletters'),
	'eval'                    => array('multiple'=>true, 'feEditable'=>true, 'feGroup'=>'newsletter'),
	'save_callback' => array
	(
		array(Newsletter::class, 'synchronize')
	),
	'sql'                     => "blob NULL"
);
