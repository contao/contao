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

use Contao\CoreBundle\DependencyInjection\Compiler\RewireTwigPathsPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Loader\FilesystemLoader;

class RewireTwigPathsPassTest extends TestCase
{
    public function testRewiresAndAddsMethodCalls(): void
    {
        $container = new ContainerBuilder();

        $baseLoader = (new Definition(FilesystemLoader::class))
            ->addMethodCall('addPath', ['path1', 'namespace1'])
            ->addMethodCall('addPath', ['path2', 'namespace2'])
            ->addMethodCall('foo')
        ;

        $loader = new Definition('contao.twig.fail_tolerant_filesystem_loader');

        $container->addDefinitions([
            'twig.loader.native_filesystem' => $baseLoader,
            'contao.twig.fail_tolerant_filesystem_loader' => $loader,
        ]);

        (new RewireTwigPathsPass())->process($container);

        $this->assertFalse($baseLoader->hasMethodCall('addPath'));
        $this->assertTrue($baseLoader->hasMethodCall('foo'));

        $expectedLoaderCalls = [
            ['addPath', ['path1', 'namespace1']],
            ['addPath', ['path2', 'namespace2']],
        ];

        $this->assertSame($expectedLoaderCalls, $loader->getMethodCalls());
    }

    public function testDoesNothingIfNoPathsAreRegistered(): void
    {
        $container = new ContainerBuilder();

        $baseLoader = (new Definition(FilesystemLoader::class))
            ->addMethodCall('foo')
        ;

        $loader = new Definition('contao.twig.fail_tolerant_filesystem_loader');

        $container->addDefinitions([
            'twig.loader.native_filesystem' => $baseLoader,
            'contao.twig.fail_tolerant_filesystem_loader' => $loader,
        ]);

        (new RewireTwigPathsPass())->process($container);

        $this->assertTrue($baseLoader->hasMethodCall('foo'));
        $this->assertEmpty($loader->getMethodCalls());
    }
}
