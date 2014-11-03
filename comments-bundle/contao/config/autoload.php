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

// Classes
ClassLoader::addClasses(array
(
	// Classes
	'Contao\Comments'            => 'vendor/contao/comments-bundle/contao/classes/Comments.php',

	// Elements
	'Contao\ContentComments'     => 'vendor/contao/comments-bundle/contao/elements/ContentComments.php',

	// Models
	'Contao\CommentsModel'       => 'vendor/contao/comments-bundle/contao/models/CommentsModel.php',
	'Contao\CommentsNotifyModel' => 'vendor/contao/comments-bundle/contao/models/CommentsNotifyModel.php',

	// Modules
	'Contao\ModuleComments'      => 'vendor/contao/comments-bundle/contao/modules/ModuleComments.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'com_default'      => 'vendor/contao/comments-bundle/contao/templates/comments',
	'ce_comments'      => 'vendor/contao/comments-bundle/contao/templates/elements',
	'mod_comment_form' => 'vendor/contao/comments-bundle/contao/templates/modules',
));
