<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version507;

class FieldPermissionMigration extends AbstractFieldPermissionMigration
{
    protected function getMapping(): array
    {
        return [
            'formp' => [
                'create' => 'tl_form::create',
                'delete' => 'tl_form::delete',
            ],
        ];
    }
}
