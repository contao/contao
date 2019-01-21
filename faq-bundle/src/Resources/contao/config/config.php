<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Add back end modules
array_insert($GLOBALS['BE_MOD']['content'], 2, array
(
	'faq' => array
	(
		'tables' => array('tl_faq_category', 'tl_faq')
	)
));

// Front end modules
array_insert($GLOBALS['FE_MOD'], 3, array
(
	'faq' => array
	(
		'faqlist'   => 'Contao\ModuleFaqList',
		'faqreader' => 'Contao\ModuleFaqReader',
		'faqpage'   => 'Contao\ModuleFaqPage'
	)
));

// Style sheet
if (\defined('TL_MODE') && TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaofaq/faq.min.css|static';
}

// Register hooks
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('Contao\ModuleFaq', 'getSearchablePages');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('contao_faq.listener.insert_tags', 'onReplaceInsertTags');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'faqs';
$GLOBALS['TL_PERMISSIONS'][] = 'faqp';
