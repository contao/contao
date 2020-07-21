<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\ModuleFaq;
use Contao\ModuleFaqList;
use Contao\ModuleFaqPage;
use Contao\ModuleFaqReader;

// Add back end modules
$GLOBALS['BE_MOD']['content']['faq'] = array
(
	'tables' => array('tl_faq_category', 'tl_faq')
);

// Front end modules
$GLOBALS['FE_MOD']['faq'] = array
(
	'faqlist'   => ModuleFaqList::class,
	'faqreader' => ModuleFaqReader::class,
	'faqpage'   => ModuleFaqPage::class
);

// Style sheet
if (defined('TL_MODE') && TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaofaq/faq.min.css|static';
}

// Register hooks
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array(ModuleFaq::class, 'getSearchablePages');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'faqs';
$GLOBALS['TL_PERMISSIONS'][] = 'faqp';

// Models
$GLOBALS['TL_MODELS']['tl_faq_category'] = FaqCategoryModel::class;
$GLOBALS['TL_MODELS']['tl_faq'] = FaqModel::class;
