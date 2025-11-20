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
use Twig\Extra\String\StringExtension;

class RegisterTwigExtensionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $stringExtension = new Definition(StringExtension::class);
        $stringExtension->addTag('twig.extension');

        $container->setDefinition('twig.extension.string', $stringExtension);
    }
}
