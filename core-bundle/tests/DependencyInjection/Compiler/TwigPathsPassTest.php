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
use Contao\CoreBundle\Twig\FailTolerantFilesystemLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Loader\FilesystemLoader;

class TwigPathsPassTest extends TestCase
{
    public function testRewiresMethodCalls(): void
    {
        $container = new ContainerBuilder();

        $originalService = (new Definition(FilesystemLoader::class))
            ->addMethodCall('addPath', ['path1', 'namespace1'])
            ->addMethodCall('addPath', ['path2', 'namespace2'])
            ->addMethodCall('foo')
        ;

        $decoratedService = new Definition(FailTolerantFilesystemLoader::class);

        $container->addDefinitions([
            'twig.loader.native_filesystem' => $originalService,
            FailTolerantFilesystemLoader::class => $decoratedService,
        ]);

        (new RewireTwigPathsPass())->process($container);

        $this->assertFalse($originalService->hasMethodCall('addPath'));
        $this->assertTrue($originalService->hasMethodCall('foo'));

        $expectedCalls = [
            ['addPath', ['path1', 'namespace1']],
            ['addPath', ['path2', 'namespace2']],
        ];

        $this->assertSame($expectedCalls, $decoratedService->getMethodCalls());
    }
}
