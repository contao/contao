<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Add content element
$GLOBALS['TL_CTE']['includes']['comments'] = 'Contao\ContentComments';

// Front end modules
$GLOBALS['FE_MOD']['application']['comments'] = 'Contao\ModuleComments';

// Back end modules
array_insert($GLOBALS['BE_MOD']['content'], 5, array
(
	'comments' => array
	(
		'tables'     => array('tl_comments'),
		'stylesheet' => 'bundles/contaocomments/comments.min.css'
	)
));

// Cron jobs
$GLOBALS['TL_CRON']['daily']['purgeCommentSubscriptions'] = array('Contao\Comments', 'purgeSubscriptions');
