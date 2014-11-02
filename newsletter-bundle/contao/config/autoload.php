<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Newsletter
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

// Classes
ClassLoader::addClasses(array
(
	// Classes
	'Contao\Newsletter'                => 'vendor/contao/newsletter-bundle/contao/classes/Newsletter.php',

	// Models
	'Contao\NewsletterChannelModel'    => 'vendor/contao/newsletter-bundle/contao/models/NewsletterChannelModel.php',
	'Contao\NewsletterModel'           => 'vendor/contao/newsletter-bundle/contao/models/NewsletterModel.php',
	'Contao\NewsletterRecipientsModel' => 'vendor/contao/newsletter-bundle/contao/models/NewsletterRecipientsModel.php',

	// Modules
	'Contao\ModuleNewsletterList'      => 'vendor/contao/newsletter-bundle/contao/modules/ModuleNewsletterList.php',
	'Contao\ModuleNewsletterReader'    => 'vendor/contao/newsletter-bundle/contao/modules/ModuleNewsletterReader.php',
	'Contao\ModuleSubscribe'           => 'vendor/contao/newsletter-bundle/contao/modules/ModuleSubscribe.php',
	'Contao\ModuleUnsubscribe'         => 'vendor/contao/newsletter-bundle/contao/modules/ModuleUnsubscribe.php',
));


// Templates
TemplateLoader::addFiles(array
(
	'mod_newsletter'        => 'vendor/contao/newsletter-bundle/contao/templates/modules',
	'mod_newsletter_list'   => 'vendor/contao/newsletter-bundle/contao/templates/modules',
	'mod_newsletter_reader' => 'vendor/contao/newsletter-bundle/contao/templates/modules',
	'nl_default'            => 'vendor/contao/newsletter-bundle/contao/templates/newsletter',
));
