<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Back end modules
$GLOBALS['BE_MOD']['accounts']['debug'] = array
(
	'enable'                  => ['Contao\ManagerBundle\Controller\DebugController', 'enableAction'],
	'disable'                 => ['Contao\ManagerBundle\Controller\DebugController', 'disableAction'],
	'hideInNavigation'        => true,
	'disablePermissionChecks' => true
);
