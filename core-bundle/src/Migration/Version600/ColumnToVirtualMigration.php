<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version600;

class ColumnToVirtualMigration extends AbstractColumnToVirtualMigration
{
    protected function getMapping(): array
    {
        return [
            'tl_content' => [
                'playerSize',
                'playerOptions',
                'playerStart',
                'playerStop',
                'playerTitle',
                'playerCaption',
                'playerAspect',
                'playerPreload',
                'playerColor',
            ],
        ];
    }
}
