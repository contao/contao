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
use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\PackageUtil;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddPackagesPassTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testAddsThePackages(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.13: Using the PackageUtil::getVersion() method has been deprecated %s.');

        $container = new ContainerBuilder();

        $pass = new AddPackagesPass();
        $pass->process($container);

        $this->assertTrue($container->hasParameter('kernel.packages'));

        $installedRaw = array_merge(...array_map(static fn ($installed) => $installed['versions'], InstalledVersions::getAllRawData()));
        $packages = $container->getParameter('kernel.packages');

        $this->assertIsArray($packages);
        $this->assertArrayNotHasKey('contao/test-bundle4', $packages);

        foreach ($installedRaw as $key => $version) {
            if (isset($version['pretty_version'])) {
                $this->assertArrayHasKey($key, $packages);
                $this->assertSame(PackageUtil::getVersion($key), $packages[$key]);
            }
        }
    }
}
