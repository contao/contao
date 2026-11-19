<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;

// Options that can only be built for an existing record, like the main language
// select of a translation extension
$GLOBALS['TL_DCA']['tl_article']['fields']['record_dependent_select'] = [
    'label' => ['Record dependent select', ''],
    'inputType' => 'select',
    'options_callback' => static function (DataContainer $dc): array {
        $record = $dc->getCurrentRecord() ?? throw new RuntimeException('The options depend on the current record.');

        return [(string) $record['pid']];
    },
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
];
