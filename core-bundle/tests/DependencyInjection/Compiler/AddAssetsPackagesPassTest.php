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

use Composer\InstalledVersions;
use Contao\CoreBundle\DependencyInjection\Compiler\AddAssetsPackagesPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class AddAssetsPackagesPassTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->mkdir(static::getTempDir().'/FooBarBundle/Resources/public');
        $fs->mkdir(static::getTempDir().'/FooBarPackage/Resources/public');

        $fs->mkdir(static::getTempDir().'/ManifestJsonBundle/Resources/public');
        $fs->touch(static::getTempDir().'/ManifestJsonBundle/Resources/public/manifest.json');

        $fs->mkdir(static::getTempDir().'/ThemeBundle/contao/themes/flexible');
        $fs->touch(static::getTempDir().'/ThemeBundle/contao/themes/flexible/manifest.json');

        $fs->mkdir(static::getTempDir().'/RootPublicBundle/public');
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
        $container = $this->getContainerWithAssets('BarFooBundle', 'Bar\Foo\BarFooBundle', $bundlePath);

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
        $container = $this->getContainerWithAssets('FooBarBundle', 'Foo\Bar\FooBarBundle', $bundlePath);

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

        $container = $this->getContainerWithAssets('FooBarBundle', 'Foo\Bar\FooBarBundle', $bundlePath);
        $container->setDefinition('assets._version_default', new Definition(StaticVersionStrategy::class));

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_foo_bar'));

        $service = $container->getDefinition('assets._package_foo_bar');

        $this->assertSame('assets._version_default', (string) $service->getArgument(1));
    }

    public function testUsesTheJsonManifestVersionStrategyForBundles(): void
    {
        $bundlePath = Path::normalize(static::getTempDir()).'/ManifestJsonBundle';
        $container = $this->getContainerWithAssets('ManifestJsonBundle', 'Foo\Bar\ManifestJsonBundle', $bundlePath);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_manifest_json'));

        $service = $container->getDefinition('assets._package_manifest_json');

        $this->assertSame('bundles/manifestjson', $service->getArgument(0));
        $this->assertSame('assets._version_manifest_json', (string) $service->getArgument(1));
        $this->assertTrue($container->hasDefinition('assets._version_manifest_json'));

        $definition = $container->getDefinition('assets._version_manifest_json');

        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('assets.json_manifest_version_strategy', $definition->getParent());
        $this->assertSame($bundlePath.'/Resources/public/manifest.json', $definition->getArgument(0));
    }

    public function testSupportsBundlesWithPublicInRoot(): void
    {
        $bundlePath = static::getTempDir().'/RootPublicBundle';

        $container = $this->getContainerWithAssets('RootPublicBundle', 'Foo\Bar\RootPublicBundle', $bundlePath);
        $container->setDefinition('assets._version_default', new Definition(StaticVersionStrategy::class));

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_root_public'));

        $service = $container->getDefinition('assets._package_root_public');

        $this->assertSame('bundles/rootpublic', $service->getArgument(0));
    }

    public function testSupportsBundlesWithWrongSuffix(): void
    {
        $bundlePath = static::getTempDir().'/FooBarPackage';
        $container = $this->getContainerWithAssets('FooBarPackage', 'Foo\Bar\FooBarPackage', $bundlePath);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_foo_bar_package'));

        $service = $container->getDefinition('assets._package_foo_bar_package');

        $this->assertSame('bundles/foobarpackage', $service->getArgument(0));
    }

    public function testRegistersTheContaoComponents(): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition('assets.packages', new Definition(Packages::class));
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.bundles_metadata', []);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_contao-components/contao'));
        $this->assertTrue($container->hasDefinition('assets._version_contao-components/contao'));
        $this->assertFalse($container->hasDefinition('assets._package_contao/image'));
        $this->assertFalse($container->hasDefinition('assets._version_contao/image'));
        $this->assertTrue($container->hasDefinition('assets._package_scrivo/highlight.php'));
        $this->assertTrue($container->hasDefinition('assets._version_scrivo/highlight.php'));

        $service = $container->getDefinition('assets._package_contao-components/contao');

        $this->assertSame('assets._version_contao-components/contao', (string) $service->getArgument(1));

        $expectedVersion = InstalledVersions::getPrettyVersion('contao-components/contao');
        $actualVersion = $container->getDefinition('assets._version_contao-components/contao')->getArgument(0);

        $this->assertSame($expectedVersion, $actualVersion);
    }

    public function testRegistersTheThemes(): void
    {
        $bundlePath = Path::normalize(static::getTempDir()).'/ThemeBundle';
        $container = $this->getContainerWithAssets('ThemeBundle', 'Foo\Bar\ThemeBundle', $bundlePath);

        $pass = new AddAssetsPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('assets._package_system/themes/flexible'));

        $service = $container->getDefinition('assets._package_system/themes/flexible');

        $this->assertSame('system/themes/flexible', $service->getArgument(0));
        $this->assertSame('assets._version_system/themes/flexible', (string) $service->getArgument(1));
        $this->assertTrue($container->hasDefinition('assets._version_system/themes/flexible'));

        $definition = $container->getDefinition('assets._version_system/themes/flexible');

        $this->assertInstanceOf(ChildDefinition::class, $definition);
        $this->assertSame('assets.json_manifest_version_strategy', $definition->getParent());
        $this->assertSame($bundlePath.'/contao/themes/flexible/manifest.json', $definition->getArgument(0));
    }

    private function getContainerWithAssets(string $name, string $class, string $path): ContainerBuilder
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition('assets.packages', new Definition(Packages::class));
        $container->setDefinition('assets.empty_version_strategy', new Definition(EmptyVersionStrategy::class));
        $container->setDefinition('assets.json_manifest_version_strategy', new Definition(JsonManifestVersionStrategy::class));
        $container->setParameter('kernel.bundles', [$name => $class]);
        $container->setParameter('kernel.bundles_metadata', [$name => ['path' => $path]]);

        return $container;
    }
}
