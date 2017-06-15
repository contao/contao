<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers the picker menu providers.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PickerMenuProviderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('contao.menu.picker_menu_builder')) {
            return;
        }

        $definition = $container->findDefinition('contao.menu.picker_menu_builder');
        $references = $this->findAndSortTaggedServices('contao.picker_menu_provider', $container);

        foreach ($references as $reference) {
            $definition->addMethodCall('addProvider', [$reference]);
        }
    }
}
