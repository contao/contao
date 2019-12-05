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
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StartStopValidatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.data_container.start_stop_validator')) {
            return;
        }

        $definition = $container->findDefinition('contao.data_container.start_stop_validator');
        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_calendar_events',
                'target' => 'fields.start.save',
                'method' => 'validateStartDate',
            ]
        );

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_calendar_events',
                'target' => 'fields.stop.save',
                'method' => 'validateStopDate',
            ]
        );
    }
}
