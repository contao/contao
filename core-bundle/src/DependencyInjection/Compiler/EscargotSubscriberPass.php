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

class EscargotSubscriberPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.search.escargot_factory')) {
            return;
        }

        $definition = $container->findDefinition('contao.search.escargot_factory');
        $references = $this->findAndSortTaggedServices('contao.escargot_subscriber', $container);

        foreach ($references as $reference) {
            $definition->addMethodCall('addSubscriber', [$reference]);
        }
    }
}
