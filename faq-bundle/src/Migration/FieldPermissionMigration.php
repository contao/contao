<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Migration;

use Contao\CoreBundle\Migration\Version507\AbstractFieldPermissionMigration;

class FieldPermissionMigration extends AbstractFieldPermissionMigration
{
    protected function getMapping(): array
    {
        return [
            'faqp' => [
                'create' => 'tl_faq_category::create',
                'delete' => 'tl_faq_category::delete',
            ],
        ];
    }
}
