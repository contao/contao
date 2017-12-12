<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AddAssetsPackagesPass;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\PackageUtil;
use PackageVersions\Versions;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Filesystem\Filesystem;

class AddAssetsPackagesPassTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fs = new Filesystem();
        $fs->mkdir(static::getTempDir().'/FooBarBundle/Resources/public');
        $fs->mkdir(static::getTempDir().'/FooBarPackage/Resources/public');
    }

    public function testCanBeInstantiated(): void
    {
        $pass = new AddAssetsPackagesPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\AddAssetsPackagesPass', $pass);
    }

    public function testAbortsIfTheAssetsPackagesServiceDoesNotExist(): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('assets.packages')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('getDefinition')
        ;

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);
    }

    public function testIgnoresBundlesWithoutPublicFolder(): void
    {
        $bundlePath = static::getTempDir().'/BarFooBundle';
        $container = $this->mockContainerWithAssets('BarFooBundle', 'Bar\Foo\BarFooBundle', $bundlePath);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $calls = $container->getDefinition('assets.packages')->getMethodCalls();
        $found = false;

        foreach ($calls as $call) {
            if ('addPackage' === $call[0] && 'bar_foo' === $call[1][0]) {
                $found = true;
                break;
            }
        }

        $this->assertFalse($found);
    }

    public function testUsesTheBundleNameAsPackageName(): void
    {
        $bundlePath = static::getTempDir().'/FooBarBundle';
        $container = $this->mockContainerWithAssets('FooBarBundle', 'Foo\Bar\FooBarBundle', $bundlePath);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $calls = $container->getDefinition('assets.packages')->getMethodCalls();
        $found = false;

        foreach ($calls as $call) {
            if ('addPackage' === $call[0] && 'foo_bar' === $call[1][0]) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
        $this->assertTrue($container->hasDefinition('assets._package_foo_bar'));

        $service = $container->getDefinition('assets._package_foo_bar');
        $this->assertSame('bundles/foobar', $service->getArgument(0));
        $this->assertSame('assets.empty_version_strategy', (string) $service->getArgument(1));
        $this->assertSame('contao.assets.assets_context', (string) $service->getArgument(2));
    }

    public function testUsesTheDefaultVersionStrategyForBundles(): void
    {
        $bundlePath = static::getTempDir().'/FooBarBundle';

        $container = $this->mockContainerWithAssets('FooBarBundle', 'Foo\Bar\FooBarBundle', $bundlePath);
        $container->setDefinition('assets._version_default', new Definition(StaticVersionStrategy::class));

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_foo_bar'));

        $service = $container->getDefinition('assets._package_foo_bar');

        $this->assertSame('assets._version_default', (string) $service->getArgument(1));
    }

    public function testSupportsBundlesWithWrongSuffix(): void
    {
        $bundlePath = static::getTempDir().'/FooBarPackage';
        $container = $this->mockContainerWithAssets('FooBarPackage', 'Foo\Bar\FooBarPackage', $bundlePath);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_foo_bar_package'));

        $service = $container->getDefinition('assets._package_foo_bar_package');

        $this->assertSame('bundles/foobarpackage', $service->getArgument(0));
    }

    public function testRegistersTheContaoComponents(): void
    {
        $container = $this->mockContainer();
        $container->setDefinition('assets.packages', new Definition(Packages::class));
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.bundles_metadata', []);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_contao-components/contao'));
        $this->assertTrue($container->hasDefinition('assets._version_contao-components/contao'));
        $this->assertFalse($container->hasDefinition('assets._package_contao/image'));
        $this->assertFalse($container->hasDefinition('assets._version_contao/image'));

        $service = $container->getDefinition('assets._package_contao-components/contao');

        $this->assertSame('assets._version_contao-components/contao', (string) $service->getArgument(1));

        $expectedVersion = PackageUtil::getVersion('contao-components/contao');
        $actualVersion = $container->getDefinition('assets._version_contao-components/contao')->getArgument(0);

        $this->assertSame($expectedVersion, $actualVersion);
    }

    /**
     * Mocks a container with assets packages.
     *
     * @param string $name
     * @param string $class
     * @param string $path
     *
     * @return ContainerBuilder
     */
    private function mockContainerWithAssets(string $name, string $class, string $path): ContainerBuilder
    {
        $container = $this->mockContainer();
        $container->setDefinition('assets.packages', new Definition(Packages::class));
        $container->setDefinition('assets.empty_version_strategy', new Definition(EmptyVersionStrategy::class));
        $container->setParameter('kernel.bundles', [$name => $class]);
        $container->setParameter('kernel.bundles_metadata', [$name => ['path' => $path]]);

        return $container;
    }
}
