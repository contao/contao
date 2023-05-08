<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AddPermissionsListenerPass implements CompilerPassInterface
{
    public function process($container): void
    {
        $permissionsListener = $container->getDefinition('contao.listener.data_container.add_permissions');
        $permissionsListener->addTag(
            'contao.callback',
            [
                'table' => 'tl_calendar',
                'target' => 'fields.permissions.input_field',
                'method' => 'generateFieldMarkup',
            ]
        );
        $permissionsListener->addTag(
            'contao.callback',
            [
                'table' => 'tl_calendar',
                'target' => 'config.onsubmit',
                'method' => 'updateUserAndGroupPermissions',
            ]
        );
    }
}
