<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CommentsModel;
use Contao\CommentsNotifyModel;
use Contao\ContentComments;
use Contao\ModuleComments;

// Add content element
$GLOBALS['TL_CTE']['includes']['comments'] = ContentComments::class;

// Front end modules
$GLOBALS['FE_MOD']['application']['comments'] = ModuleComments::class;

// Back end modules
$GLOBALS['BE_MOD']['content']['comments'] = array
(
	'tables'     => array('tl_comments'),
	'stylesheet' => 'bundles/contaocomments/comments.min.css'
);

// Models
$GLOBALS['TL_MODELS']['tl_comments'] = CommentsModel::class;
$GLOBALS['TL_MODELS']['tl_comments_notify'] = CommentsNotifyModel::class;
