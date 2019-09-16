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
	'enable'                  => ['Contao\ManagerBundle\EventListener\DebugListener', 'onEnable'],
	'disable'                 => ['Contao\ManagerBundle\EventListener\DebugListener', 'onDisable'],
	'hideInNavigation'        => true,
	'disablePermissionChecks' => true
);
