<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\DependencyInjection;

use Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ContaoManagerExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoManagerExtensionTest extends TestCase
{
    /**
     * @var ContaoManagerExtension
     */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->extension = new ContaoManagerExtension();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension', $this->extension);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\DependencyInjection\Extension', $this->extension);
    }

    /**
     * Tests the load() method.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        $this->assertTrue($container->has('contao_manager.plugin_loader'));
        $this->assertTrue($container->has('contao_manager.routing_loader'));
    }
}
