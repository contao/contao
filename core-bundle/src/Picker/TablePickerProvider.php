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

class TablePickerProvider extends AbstractTablePickerProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'tablePicker';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataContainer(): string
    {
        return 'Table';
    }
}
