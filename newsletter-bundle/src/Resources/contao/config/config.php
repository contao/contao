<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ModuleNewsletterList;
use Contao\ModuleNewsletterReader;
use Contao\ModuleSubscribe;
use Contao\ModuleUnsubscribe;
use Contao\Newsletter;
use Contao\NewsletterChannelModel;
use Contao\NewsletterDenyListModel;
use Contao\NewsletterModel;
use Contao\NewsletterRecipientsModel;

// Back end modules
$GLOBALS['BE_MOD']['content']['newsletter'] = array
(
	'tables'     => array('tl_newsletter_channel', 'tl_newsletter', 'tl_newsletter_recipients'),
	'send'       => array(Newsletter::class, 'send'),
	'import'     => array(Newsletter::class, 'importRecipients'),
	'stylesheet' => 'bundles/contaonewsletter/newsletter.min.css'
);

// Front end modules
$GLOBALS['FE_MOD']['newsletter'] = array
(
	'subscribe'        => ModuleSubscribe::class,
	'unsubscribe'      => ModuleUnsubscribe::class,
	'newsletterlist'   => ModuleNewsletterList::class,
	'newsletterreader' => ModuleNewsletterReader::class
);

// Register hooks
$GLOBALS['TL_HOOKS']['createNewUser'][] = array(Newsletter::class, 'createNewUser');
$GLOBALS['TL_HOOKS']['activateAccount'][] = array(Newsletter::class, 'activateAccount');
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array(Newsletter::class, 'getSearchablePages');
$GLOBALS['TL_HOOKS']['closeAccount'][] = array(Newsletter::class, 'removeSubscriptions');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'newsletters';
$GLOBALS['TL_PERMISSIONS'][] = 'newsletterp';

// Cron jobs
$GLOBALS['TL_CRON']['daily']['purgeNewsletterSubscriptions'] = array(Newsletter::class, 'purgeSubscriptions');

// Models
$GLOBALS['TL_MODELS']['tl_newsletter_channel'] = NewsletterChannelModel::class;
$GLOBALS['TL_MODELS']['tl_newsletter_deny_list'] = NewsletterDenyListModel::class;
$GLOBALS['TL_MODELS']['tl_newsletter'] = NewsletterModel::class;
$GLOBALS['TL_MODELS']['tl_newsletter_recipients'] = NewsletterRecipientsModel::class;
