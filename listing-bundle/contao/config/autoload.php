<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Listing
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

// Classes
ClassLoader::addClasses(array
(
	// Modules
	'Contao\ModuleListing' => 'vendor/contao/listing-bundle/contao/modules/ModuleListing.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'info_default' => 'vendor/contao/listing-bundle/contao/templates/info',
	'list_default' => 'vendor/contao/listing-bundle/contao/templates/listing',
));
