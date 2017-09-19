<?php

declare(strict_types=1);

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
 * Registers the picker providers.
 */
class PickerProviderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.picker.builder')) {
            return;
        }

        $definition = $container->findDefinition('contao.picker.builder');
        $references = $this->findAndSortTaggedServices('contao.picker_provider', $container);

        foreach ($references as $reference) {
            $definition->addMethodCall('addProvider', [$reference]);
        }
    }
}
