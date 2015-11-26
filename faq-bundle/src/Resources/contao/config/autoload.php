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
	// Models
	'Contao\FaqCategoryModel' => 'vendor/contao/faq-bundle/src/Resources/contao/models/FaqCategoryModel.php',
	'Contao\FaqModel'         => 'vendor/contao/faq-bundle/src/Resources/contao/models/FaqModel.php',

	// Modules
	'Contao\ModuleFaq'        => 'vendor/contao/faq-bundle/src/Resources/contao/modules/ModuleFaq.php',
	'Contao\ModuleFaqList'    => 'vendor/contao/faq-bundle/src/Resources/contao/modules/ModuleFaqList.php',
	'Contao\ModuleFaqPage'    => 'vendor/contao/faq-bundle/src/Resources/contao/modules/ModuleFaqPage.php',
	'Contao\ModuleFaqReader'  => 'vendor/contao/faq-bundle/src/Resources/contao/modules/ModuleFaqReader.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'mod_faqlist'   => 'vendor/contao/faq-bundle/src/Resources/contao/templates/modules',
	'mod_faqpage'   => 'vendor/contao/faq-bundle/src/Resources/contao/templates/modules',
	'mod_faqreader' => 'vendor/contao/faq-bundle/src/Resources/contao/templates/modules',
));
