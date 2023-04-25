<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Core version
define('VERSION', '4.9');
define('BUILD', '40');
define('LONG_TERM_SUPPORT', true);

// Link constants
define('LINK_BLUR', ' onclick="this.blur()"');
define('LINK_NEW_WINDOW', ' onclick="return !window.open(this.href)"');
define('LINK_NEW_WINDOW_BLUR', ' onclick="this.blur();return !window.open(this.href)"');

// Log constants
define('TL_ERROR', 'ERROR');
define('TL_ACCESS', 'ACCESS');
define('TL_GENERAL', 'GENERAL');
define('TL_FILES', 'FILES');
define('TL_CRON', 'CRON');
define('TL_FORMS', 'FORMS');
define('TL_EMAIL', 'EMAIL');
define('TL_CONFIGURATION', 'CONFIGURATION');
define('TL_NEWSLETTER', 'NEWSLETTER');
define('TL_REPOSITORY', 'REPOSITORY');
