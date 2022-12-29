<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_member']['config']['ctable'][] = 'tl_member_oauth';
$GLOBALS['TL_DCA']['tl_member']['list']['operations']['oauth'] = [
    'href' => 'table=tl_member_oauth',
    'icon' => 'sync.svg',
];
