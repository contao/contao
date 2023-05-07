<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

#[AsCallback(table: 'tl_user_group', target: 'fields.frontendModules.options')]
#[AsCallback(table: 'tl_user', target: 'fields.frontendModules.options')]
class FrontendModuleOptionsListener
{
    public function __invoke(DataContainer $dc): array
    {
        return array_map('array_keys', $GLOBALS['FE_MOD']);
    }
}
