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
	// Modules
	'Contao\ModuleListing' => 'vendor/contao/listing-bundle/src/Resources/contao/modules/ModuleListing.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'info_default' => 'vendor/contao/listing-bundle/src/Resources/contao/templates/info',
	'list_default' => 'vendor/contao/listing-bundle/src/Resources/contao/templates/listing',
));
