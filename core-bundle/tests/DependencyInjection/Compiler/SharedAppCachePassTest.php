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

use Contao\CoreBundle\DependencyInjection\Compiler\SharedAppCachePass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SharedAppCachePassTest extends TestCase
{
    public function testAddsSharedAppCacheDefintion(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('cache.app', new Definition());

        $pass = new SharedAppCachePass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('contao.cache.shared'));

        $definition = $container->getDefinition('contao.cache.shared');

        $this->assertInstanceOf(ChildDefinition::class, $definition);

        /** @var ChildDefinition $definition */
        $this->assertSame('cache.app', $definition->getParent());

        $this->assertCount(0, $definition->getArguments());
    }

    public function testSetsFilesystemCacheDirectory(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('cache.adapter.filesystem', new Definition());
        $container->setDefinition('cache.app', new ChildDefinition('cache.adapter.filesystem'));

        $pass = new SharedAppCachePass();
        $pass->process($container);

        $definition = $container->getDefinition('contao.cache.shared');

        $this->assertSame('%kernel.project_dir%/var/cache/shared', $definition->getArgument('$directory'));
    }
}
