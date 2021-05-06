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

use Contao\CoreBundle\DependencyInjection\Compiler\TwigPathsPass;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\FilesystemLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Loader\FilesystemLoader as BaseFilesystemLoader;
use Webmozart\PathUtil\Path;

class TwigPathsPassTest extends TestCase
{
    public function testRewiresAndAddsMethodCalls(): void
    {
        $container = new ContainerBuilder();

        $defaultPath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/templates');
        $testBundlePath = Path::canonicalize(__DIR__.'/../../Fixtures/vendor/contao/test-bundle');

        $container->setParameter('twig.default_path', $defaultPath);
        $container->setParameter('kernel.bundles_metadata', ['TestBundle' => ['path' => $testBundlePath]]);

        $baseLoader = (new Definition(BaseFilesystemLoader::class))
            ->addMethodCall('addPath', ['path1', 'namespace1'])
            ->addMethodCall('addPath', ['path2', 'namespace2'])
            ->addMethodCall('foo')
        ;

        $loader = new Definition(FilesystemLoader::class);

        $container->addDefinitions([
            'twig.loader.native_filesystem' => $baseLoader,
            FilesystemLoader::class => $loader,
        ]);

        (new TwigPathsPass())->process($container);

        $this->assertFalse($baseLoader->hasMethodCall('addPath'));
        $this->assertTrue($baseLoader->hasMethodCall('foo'));

        $expectedCalls = [
            // Rewired
            ['addPath', ['path1', 'namespace1']],
            ['addPath', ['path2', 'namespace2']],
            // Added
            ['addPath', [Path::join($defaultPath, 'contao'), 'ContaoLegacy']],
            ['addPath', [Path::join($testBundlePath, 'Resources/views/contao'), 'ContaoLegacy']],
            ['addPath', [Path::join($defaultPath, 'contao/foo-theme'), 'ContaoLegacy_foo-theme']],
        ];

        $this->assertSame($expectedCalls, $loader->getMethodCalls());
    }
}
