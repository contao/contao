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

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Mailer\Transport\NativeTransportFactory;

/**
 * @internal
 */
class AddNativeTransportFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!class_exists(NativeTransportFactory::class) || $container->hasDefinition('mailer.transport_factory.native')) {
            return;
        }

        $definition = new ChildDefinition('mailer.transport_factory.abstract');
        $definition
            ->setClass(NativeTransportFactory::class)
            ->addTag('mailer.transport_factory')
        ;

        $container->setDefinition('mailer.transport_factory.native', $definition);
    }
}
