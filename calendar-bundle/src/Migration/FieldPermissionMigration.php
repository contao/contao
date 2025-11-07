<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Migration;

use Contao\CoreBundle\Migration\Version507\FieldPermissionMigration as CoreFieldPermissionMigration;

class FieldPermissionMigration extends CoreFieldPermissionMigration
{
    protected function getMapping(): array
    {
        return [
            'calendarp' => [
                'create' => 'tl_calendar::create',
                'delete' => 'tl_calendar::delete',
            ],
            'calendarfeedp' => [
                'create' => 'tl_calendar_feed::create',
                'delete' => 'tl_calendar_feed::delete',
            ],
        ];
    }
}
