<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Test\DependencyInjection;

use Contao\NewsBundle\DependencyInjection\ContaoNewsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the ContaoNewsExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoNewsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoNewsExtension();

        $this->assertInstanceOf('Contao\NewsBundle\DependencyInjection\ContaoNewsExtension', $extension);
    }

    /**
     * Tests adding the bundle services to the container.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension = new ContaoNewsExtension();
        $extension->load([], $container);

        $this->assertTrue($container->has('contao_news.listener.preview_url_create'));
        $this->assertTrue($container->has('contao_news.listener.preview_url_convert'));
    }
}
