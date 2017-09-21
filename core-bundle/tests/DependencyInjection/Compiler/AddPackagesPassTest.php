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

use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddPackagesPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new AddPackagesPass($this->getRootDir().'/vendor/composer/installed.json');

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass', $pass);
    }

    public function testAddsThePackages(): void
    {
        $pass = new AddPackagesPass($this->getRootDir().'/vendor/composer/installed.json');
        $container = new ContainerBuilder();

        $pass->process($container);

        $this->assertTrue($container->hasParameter('kernel.packages'));

        $packages = $container->getParameter('kernel.packages');

        $this->assertInternalType('array', $packages);
        $this->assertArrayHasKey('contao/test-bundle1', $packages);
        $this->assertArrayHasKey('contao/test-bundle2', $packages);
        $this->assertArrayNotHasKey('contao/test-bundle3', $packages);

        $this->assertSame('1.0.0', $packages['contao/test-bundle1']);
        $this->assertSame('dev-develop', $packages['contao/test-bundle2']);
    }

    public function testAddsAnEmptyArrayIfThereIsNoJsonFile(): void
    {
        $pass = new AddPackagesPass($this->getRootDir().'/vendor/composer/invalid.json');
        $container = new ContainerBuilder();

        $pass->process($container);

        $this->assertTrue($container->hasParameter('kernel.packages'));

        $packages = $container->getParameter('kernel.packages');

        $this->assertInternalType('array', $packages);
        $this->assertEmpty($container->getParameter('kernel.packages'));
    }
}
