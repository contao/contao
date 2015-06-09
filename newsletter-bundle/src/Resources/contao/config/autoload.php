<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// Classes
ClassLoader::addClasses(array
(
	// Classes
	'Contao\Newsletter'                => 'vendor/contao/newsletter-bundle/src/Resources/contao/classes/Newsletter.php',

	// Models
	'Contao\NewsletterChannelModel'    => 'vendor/contao/newsletter-bundle/src/Resources/contao/models/NewsletterChannelModel.php',
	'Contao\NewsletterModel'           => 'vendor/contao/newsletter-bundle/src/Resources/contao/models/NewsletterModel.php',
	'Contao\NewsletterRecipientsModel' => 'vendor/contao/newsletter-bundle/src/Resources/contao/models/NewsletterRecipientsModel.php',

	// Modules
	'Contao\ModuleNewsletterList'      => 'vendor/contao/newsletter-bundle/src/Resources/contao/modules/ModuleNewsletterList.php',
	'Contao\ModuleNewsletterReader'    => 'vendor/contao/newsletter-bundle/src/Resources/contao/modules/ModuleNewsletterReader.php',
	'Contao\ModuleSubscribe'           => 'vendor/contao/newsletter-bundle/src/Resources/contao/modules/ModuleSubscribe.php',
	'Contao\ModuleUnsubscribe'         => 'vendor/contao/newsletter-bundle/src/Resources/contao/modules/ModuleUnsubscribe.php',
));


// Templates
TemplateLoader::addFiles(array
(
	'mod_newsletter'       => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/modules',
	'mod_newsletterlist'   => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/modules',
	'mod_newsletterreader' => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/modules',
	'nl_default'           => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/newsletter',
));
