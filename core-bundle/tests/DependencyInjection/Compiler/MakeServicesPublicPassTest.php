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

use Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MakeServicesPublicPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new MakeServicesPublicPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass', $pass);
    }

    public function testMakesTheServicesPublic(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('security.firewall.map', new Definition());

        $pass = new MakeServicesPublicPass();
        $pass->process($container);

        $this->assertTrue($container->getDefinition('security.firewall.map')->isPublic());
    }
}
