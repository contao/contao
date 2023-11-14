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
    #[\Override]
    public function getName(): string
    {
        return 'tablePicker';
    }

    #[\Override]
    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = parent::getDcaAttributes($config);

        if (\is_array($rootNodes = $config->getExtra('rootNodes'))) {
            $attributes['rootNodes'] = $rootNodes;
        }

        return $attributes;
    }

    #[\Override]
    protected function getDataContainer(): string
    {
        return DC_Table::class;
    }
}
