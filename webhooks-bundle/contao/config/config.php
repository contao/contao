<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['BE_MOD']['webhooks'] = array
(
	'webhook_ingoing' => array
	(
		'tables' => array('tl_webhook_ingoing'),
	),
	'webhook_outgoing' => array
	(
		'tables' => array('tl_webhook_outgoing')
	),
);
