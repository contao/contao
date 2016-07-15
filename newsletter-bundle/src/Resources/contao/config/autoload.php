<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// Templates
TemplateLoader::addFiles(array
(
	'mod_newsletter'       => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/modules',
	'mod_newsletterlist'   => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/modules',
	'mod_newsletterreader' => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/modules',
	'nl_default'           => 'vendor/contao/newsletter-bundle/src/Resources/contao/templates/newsletter',
));
