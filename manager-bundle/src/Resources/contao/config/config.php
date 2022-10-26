<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerBundle\Controller\DebugController;

// Back end modules
$GLOBALS['BE_MOD']['accounts']['debug'] = array
(
	'enable'                  => array(DebugController::class, 'enableAction'),
	'disable'                 => array(DebugController::class, 'disableAction'),
	'hideInNavigation'        => true,
	'disablePermissionChecks' => true
);
