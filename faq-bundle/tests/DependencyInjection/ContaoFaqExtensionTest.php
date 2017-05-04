<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\Tests\DependencyInjection;

use Contao\FaqBundle\DependencyInjection\ContaoFaqExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the ContaoFaqExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoFaqExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoFaqExtension();

        $this->assertInstanceOf('Contao\FaqBundle\DependencyInjection\ContaoFaqExtension', $extension);
    }

    /**
     * Tests adding the bundle services to the container.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension = new ContaoFaqExtension();
        $extension->load([], $container);

        $this->assertTrue($container->has('contao_faq.listener.insert_tags'));
        $this->assertTrue($container->has('contao_faq.listener.faq_picker_provider'));
    }
}
