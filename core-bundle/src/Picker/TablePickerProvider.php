<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Contao\DC_Table;

class TablePickerProvider extends AbstractTablePickerProvider
{
    public function getName(): string
    {
        return 'tablePicker';
    }

    protected function getDataContainer(): string
    {
        return DC_Table::class;
    }
}
