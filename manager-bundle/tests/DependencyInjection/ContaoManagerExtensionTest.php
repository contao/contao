<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests\DependencyInjection;

use Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoManagerExtensionTest extends TestCase
{
    /**
     * @var ContaoManagerExtension
     */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->extension = new ContaoManagerExtension();
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension', $this->extension);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\DependencyInjection\Extension', $this->extension);
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        $this->assertTrue($container->has('contao_manager.plugin_loader'));
        $this->assertTrue($container->has('contao_manager.routing_loader'));
    }
}
