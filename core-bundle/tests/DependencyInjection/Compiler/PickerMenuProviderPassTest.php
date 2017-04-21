<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\PickerMenuProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests the PickerMenuProviderPass class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class PickerMenuProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new PickerMenuProviderPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\PickerMenuProviderPass', $pass);
    }

    /**
     * Tests processing the pass.
     */
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->setDefinition(
            'contao.menu.picker_menu_builder',
            new Definition('Contao\CoreBundle\Menu\PickerMenuBuilder')
        );

        $provider = new Definition('Contao\CoreBundle\Menu\PagePickerProvider');
        $provider->addTag('contao.picker_menu_provider');

        $container->setDefinition('contao.menu.page_picker_provider', $provider);

        $pass = new PickerMenuProviderPass();
        $pass->process($container);

        $methodCalls = $container->findDefinition('contao.menu.picker_menu_builder')->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertEquals('addProvider', $methodCalls[0][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $methodCalls[0][1][0]);
        $this->assertEquals('contao.menu.page_picker_provider', (string) $methodCalls[0][1][0]);
    }

    /**
     * Tests processing the pass without a session.
     */
    public function testProcessWithoutSession()
    {
        $container = new ContainerBuilder();

        $pass = new PickerMenuProviderPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao.menu.picker_menu_builder'));
    }
}
