<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\DependencyInjection;

use Contao\CalendarBundle\DependencyInjection\ContaoCalendarExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the ContaoCalendarExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCalendarExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoCalendarExtension();

        $this->assertInstanceOf('Contao\CalendarBundle\DependencyInjection\ContaoCalendarExtension', $extension);
    }

    /**
     * Tests adding the bundle services to the container.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension = new ContaoCalendarExtension();
        $extension->load([], $container);

        $this->assertTrue($container->has('contao_calendar.listener.generate_page'));
        $this->assertTrue($container->has('contao_calendar.listener.insert_tags'));
        $this->assertTrue($container->has('contao_calendar.listener.preview_url_create'));
        $this->assertTrue($container->has('contao_calendar.listener.preview_url_convert'));
    }
}
