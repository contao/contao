<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
class TranslationDataCollectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('translator.data_collector')) {
            return;
        }

        $definition = $container->getDefinition('contao.translation.data_collector_translator');
        $definition->setDecoratedService('translator');

        $definition = $container->getDefinition('data_collector.translation');
        $definition->replaceArgument(0, new Reference('contao.translation.data_collector_translator'));
    }
}
