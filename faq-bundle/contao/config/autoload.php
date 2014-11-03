<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Faq
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

// Classes
ClassLoader::addClasses(array
(
	// Models
	'Contao\FaqCategoryModel' => 'vendor/contao/faq-bundle/contao/models/FaqCategoryModel.php',
	'Contao\FaqModel'         => 'vendor/contao/faq-bundle/contao/models/FaqModel.php',

	// Modules
	'Contao\ModuleFaq'        => 'vendor/contao/faq-bundle/contao/modules/ModuleFaq.php',
	'Contao\ModuleFaqList'    => 'vendor/contao/faq-bundle/contao/modules/ModuleFaqList.php',
	'Contao\ModuleFaqPage'    => 'vendor/contao/faq-bundle/contao/modules/ModuleFaqPage.php',
	'Contao\ModuleFaqReader'  => 'vendor/contao/faq-bundle/contao/modules/ModuleFaqReader.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'mod_faqlist'   => 'vendor/contao/faq-bundle/contao/templates/modules',
	'mod_faqpage'   => 'vendor/contao/faq-bundle/contao/templates/modules',
	'mod_faqreader' => 'vendor/contao/faq-bundle/contao/templates/modules',
));
