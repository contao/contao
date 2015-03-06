<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\SetApplicationPass;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests the SetApplicationPass class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SetApplicationPassText extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new SetApplicationPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\SetApplicationPass', $pass);
    }

    /**
     * Tests processing the pass with the collector enabled.
     */
    public function testProcessWithCollector()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('data_collector.config', new Definition());

        $pass = new SetApplicationPass();
        $pass->process($container);

        $definition = $container->findDefinition('data_collector.config');

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Definition', $definition);
        $this->assertEquals(['Contao', '4.0.0'], $definition->getArguments());
    }

    /**
     * Tests processing the pass with the collector disabled.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testProcessWithoutCollector()
    {
        $container = new ContainerBuilder();

        $pass = new SetApplicationPass();
        $pass->process($container);

        $definition = $container->findDefinition('data_collector.config');

        $this->assertNull($definition);
    }
}
