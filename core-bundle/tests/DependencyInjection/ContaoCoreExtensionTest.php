<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DependencyInjection;

use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ContaoCoreExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCoreExtensionTest extends TestCase
{
    /**
     * @var ContaoCoreExtension
     */
    protected $extension;

    /**
     * Creates the core extension object.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->extension = new ContaoCoreExtension();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\\CoreBundle\\DependencyInjection\\ContaoCoreExtension', $this->extension);
    }

    /**
     * Tests adding the bundle services to the container.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder();

        $params = [
            'contao' => [
                'encryption_key' => 'foobar'
            ]
        ];

        $this->extension->load($params, $container);

        $this->assertTrue($container->has('contao.listener.output_from_cache'));
        $this->assertTrue($container->has('contao.listener.add_to_search_index'));
    }

    /**
     * Tests the getAlias() method.
     */
    public function testGetAlias()
    {
        $this->assertEquals('contao', $this->extension->getAlias());
    }
}
