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

// Templates
TemplateLoader::addFiles(array
(
	'mod_newsletter'        => 'vendor/contao/newsletter-bundle/templates/modules',
	'mod_newsletter_list'   => 'vendor/contao/newsletter-bundle/templates/modules',
	'mod_newsletter_reader' => 'vendor/contao/newsletter-bundle/templates/modules',
	'nl_default'            => 'vendor/contao/newsletter-bundle/templates/newsletter',
));
