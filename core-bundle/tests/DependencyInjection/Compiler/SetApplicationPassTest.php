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
 * @author Leo Feyer <http://github.com/leofeyer>
 */
class SetApplicationPassTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new SetApplicationPass();

        $this->assertInstanceOf('Contao\\CoreBundle\\DependencyInjection\\Compiler\\SetApplicationPass', $pass);
    }

    /**
     * Tests processing the pass.
     */
    public function testProcess()
    {
        $pass       = new SetApplicationPass();
        $container  = new ContainerBuilder();
        $definition = new Definition();

        $container->setDefinition('data_collector.config', $definition);
        $container->setParameter('kernel.packages', ['contao/core-bundle' => '4.0.2']);

        $pass->process($container);

        $this->assertEquals(['Contao', '4.0.2'], $definition->getArguments());
    }

    /**
     * Tests processing the pass without the definition.
     */
    public function testProcessWithoutDefinition()
    {
        $pass      = new SetApplicationPass();
        $container = new ContainerBuilder();

        $pass->process($container);

        $this->assertFalse($container->hasDefinition('data_collector.config'));
    }
}
