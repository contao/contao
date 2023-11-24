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
use Contao\ModuleFaqList;
use Contao\ModuleFaqPage;
use Contao\ModuleFaqReader;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

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
if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaofaq/faq.min.css|static';
}

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'faqs';
$GLOBALS['TL_PERMISSIONS'][] = 'faqp';

// Models
$GLOBALS['TL_MODELS']['tl_faq_category'] = FaqCategoryModel::class;
$GLOBALS['TL_MODELS']['tl_faq'] = FaqModel::class;
