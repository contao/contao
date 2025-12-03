<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterTwigExtensionsPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Twig\Extra\String\StringExtension;

class RegisterTwigExtensionsPassTest extends TestCase
{
    public function testRegistersStringExtension(): void
    {
        $container = new ContainerBuilder();

        (new RegisterTwigExtensionsPass())->process($container);

        $this->assertTrue($container->hasDefinition('twig.extension.string'));

        $definition = $container->getDefinition('twig.extension.string');

        $this->assertSame(StringExtension::class, $definition->getClass());
        $this->assertArrayHasKey('twig.extension', $definition->getTags());
    }
}
