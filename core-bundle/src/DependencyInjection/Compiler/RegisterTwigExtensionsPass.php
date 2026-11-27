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
use Symfony\Component\DependencyInjection\Definition;
use Twig\Extra\Intl\IntlExtension;

class RegisterTwigExtensionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (class_exists(IntlExtension::class)) {
            $intlExtension = new Definition(IntlExtension::class);
            $intlExtension->addTag('twig.extension', ['priority' => 1]);

            $container->setDefinition('twig.extension.intl', $intlExtension);
        }
    }
}
