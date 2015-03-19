<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\OptimizeContaoResourcesPass;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests the OptimizeContaoResourcesPassTest class.
 *
 * @author Andreas Schempp <http://github.com/aschempp>
 */
class OptimizeContaoResourcesPassTest extends TestCase
{
    /**
     * @var OptimizeContaoResourcesPass
     */
    private $pass;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Creates a new Contao module bundle.
     */
    protected function setUp()
    {
        $this->pass      = new OptimizeContaoResourcesPass($this->getRootDir() . '/app');
        $this->container = new ContainerBuilder();

        $this->container->setDefinition(
            'contao.resource_provider',
            new Definition('Contao\\CoreBundle\\HttpKernel\\Bundle\\ResourceProvider')
        );
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\DependencyInjection\Compiler\OptimizeContaoResourcesPass',
            $this->pass
        );
    }

    /**
     * Tests processing the pass without defintion.
     */
    public function testWithoutDefinition()
    {
        $this->pass->process(new ContainerBuilder());
    }

    /**
     * Tests adding a legacy module.
     */
    public function testModulePaths()
    {
        $definition = $this->container->getDefinition('contao.resource_provider');
        $definition->addMethodCall('addResourcesPath', [$this->getRootDir() . '/system/modules/foobar']);
        $definition->addMethodCall('addPublicFolders', [[$this->getRootDir() . '/system/modules/foobar/assets']]);

        $this->pass->process($this->container);

        $this->assertContains($this->getRootDir() . '/system/modules/foobar', $definition->getArgument(0));
        $this->assertContains('system/modules/foobar/assets', $definition->getArgument(1));
    }

    /**
     * Tests adding an invalid resource.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidResourcesPath()
    {
        $definition = $this->container->getDefinition('contao.resource_provider');
        $definition->addMethodCall('addResourcesPath', ['/system/invalid']);

        $this->pass->process($this->container);
    }

    /**
     * Tests adding an invalid public folder.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidPublicFolders()
    {
        $definition = $this->container->getDefinition('contao.resource_provider');
        $definition->addMethodCall('addPublicFolders', [['/system/invalid']]);

        $this->pass->process($this->container);
    }
}
