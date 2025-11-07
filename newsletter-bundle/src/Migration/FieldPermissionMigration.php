<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Migration;

use Contao\CoreBundle\Migration\Version507\FieldPermissionMigration as CoreFieldPermissionMigration;

class FieldPermissionMigration extends CoreFieldPermissionMigration
{
    protected function getMapping(): array
    {
        return [
            'newsletterp' => [
                'create' => 'tl_newsletter_channel::create',
                'delete' => 'tl_newsletter_channel::delete',
            ],
        ];
    }
}
