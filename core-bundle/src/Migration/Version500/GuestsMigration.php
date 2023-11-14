<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version500;

/**
 * @internal
 */
class GuestsMigration extends AbstractGuestsMigration
{
    #[\Override]
    protected function getTables(): array
    {
        return [
            'tl_article',
            'tl_content',
            'tl_module',
            'tl_page',
        ];
    }
}
