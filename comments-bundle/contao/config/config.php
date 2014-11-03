<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Comments
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Add content element
 */
$GLOBALS['TL_CTE']['includes']['comments'] = 'ContentComments';


/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['application']['comments'] = 'ModuleComments';


/**
 * Back end modules
 */
array_insert($GLOBALS['BE_MOD']['content'], 5, array
(
	'comments' => array
	(
		'tables'     => array('tl_comments'),
		'icon'       => 'vendor/contao/comments-bundle/contao/assets/icon.gif',
		'stylesheet' => 'vendor/contao/comments-bundle/contao/assets/style.css'
	)
));
