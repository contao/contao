<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\OAuthBundle\Model\OAuthClientModel;

$GLOBALS['BE_MOD']['system']['oauth'] = array(
	'tables' => array('tl_oauth_client'),
);

$GLOBALS['BE_MOD']['accounts']['member']['tables'][] = 'tl_member_oauth';

$GLOBALS['TL_MODELS']['tl_oauth_client'] = OAuthClientModel::class;
