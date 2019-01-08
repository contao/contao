<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Back end modules
array_insert($GLOBALS['BE_MOD']['content'], 4, array
(
	'newsletter' => array
	(
		'tables'     => array('tl_newsletter_channel', 'tl_newsletter', 'tl_newsletter_recipients'),
		'send'       => array('Contao\Newsletter', 'send'),
		'import'     => array('Contao\Newsletter', 'importRecipients'),
		'stylesheet' => 'bundles/contaonewsletter/newsletter.min.css'
	)
));

// Front end modules
array_insert($GLOBALS['FE_MOD'], 4, array
(
	'newsletter' => array
	(
		'subscribe'        => 'Contao\ModuleSubscribe',
		'unsubscribe'      => 'Contao\ModuleUnsubscribe',
		'newsletterlist'   => 'Contao\ModuleNewsletterList',
		'newsletterreader' => 'Contao\ModuleNewsletterReader'
	)
));

// Register hooks
$GLOBALS['TL_HOOKS']['createNewUser'][] = array('Contao\Newsletter', 'createNewUser');
$GLOBALS['TL_HOOKS']['activateAccount'][] = array('Contao\Newsletter', 'activateAccount');
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('Contao\Newsletter', 'getSearchablePages');
$GLOBALS['TL_HOOKS']['closeAccount'][] = array('Contao\Newsletter', 'removeSubscriptions');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'newsletters';
$GLOBALS['TL_PERMISSIONS'][] = 'newsletterp';

// Cron jobs
$GLOBALS['TL_CRON']['daily']['purgeNewsletterSubscriptions'] = array('Contao\Newsletter', 'purgeSubscriptions');
