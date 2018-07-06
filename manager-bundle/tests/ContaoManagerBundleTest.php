<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests;

use Contao\ManagerBundle\ContaoManagerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoManagerBundleTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManagerBundle', new ContaoManagerBundle());
    }

    public function testBuild(): void
    {
        $container = new ContainerBuilder();

        $bundle = new ContaoManagerBundle();
        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $this->assertInstanceOf('Contao\ManagerBundle\DependencyInjection\Compiler\SwiftMailerPass', $passes[3]);
    }
}
