<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Migration;

use Contao\CoreBundle\Migration\Version507\AbstractFieldPermissionMigration;

class FieldPermissionMigration extends AbstractFieldPermissionMigration
{
    protected function getMapping(): array
    {
        return [
            'newp' => [
                'create' => 'tl_news_archive::create',
                'delete' => 'tl_news_archive::delete',
            ],
        ];
    }
}
