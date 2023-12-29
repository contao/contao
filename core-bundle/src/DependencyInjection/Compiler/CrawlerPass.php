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
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CrawlerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $subscribers = $this->findAndSortTaggedServices('contao.escargot_subscriber', $container);

        if (!$subscribers || !$container->hasDefinition('contao.crawl.escargot.factory')) {
            // Remove factory
            $container->removeDefinition('contao.crawl.escargot.factory');

            // Remove crawl command
            $container->removeDefinition('contao.command.crawl');

            return;
        }

        $definition = $container->findDefinition('contao.crawl.escargot.factory');

        foreach ($subscribers as $reference) {
            $definition->addMethodCall('addSubscriber', [$reference]);
        }
    }
}
