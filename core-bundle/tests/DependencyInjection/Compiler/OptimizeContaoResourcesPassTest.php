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
            'contao.resources',
            new Definition('Contao\\CoreBundle\\HttpKernel\\Bundle\\ResourcesProvider')
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

    public function testWithoutDefinition()
    {
        $this->pass->process(new ContainerBuilder());
    }

    public function testAbsolutePaths()
    {
        $definition = $this->container->getDefinition('contao.resources');
        $definition->addMethodCall('addResourcesPath', ['testBundle', $this->getRootDir() . '/system/modules/legacy-module']);
        $definition->addMethodCall('addPublicFolders', [[$this->getRootDir() . '/system/modules/legacy-module/assets']]);

        $this->pass->process($this->container);

        $this->assertContains($this->getRootDir() . '/system/modules/legacy-module', $definition->getArgument(0));
        $this->assertContains('system/modules/legacy-module/assets', $definition->getArgument(1));
    }

    public function testRelativePaths()
    {
        $definition = $this->container->getDefinition('contao.resources');
        $definition->addMethodCall('addResourcesPath', ['testBundle', $this->getRootDir() . '/system/invalid/../modules/legacy-module']);
        $definition->addMethodCall('addPublicFolders', [[$this->getRootDir() . '/system/invalid/../modules/legacy-module/assets']]);

        $this->pass->process($this->container);

        $this->assertContains($this->getRootDir() . '/system/modules/legacy-module', $definition->getArgument(0));
        $this->assertContains('system/modules/legacy-module/assets', $definition->getArgument(1));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidResourcesPath()
    {
        $definition = $this->container->getDefinition('contao.resources');
        $definition->addMethodCall('addResourcesPath', ['testBundle', '/system/invalid']);

        $this->pass->process($this->container);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidPublicFolders()
    {
        $definition = $this->container->getDefinition('contao.resources');
        $definition->addMethodCall('addPublicFolders', [['/system/invalid']]);

        $this->pass->process($this->container);
    }
}
