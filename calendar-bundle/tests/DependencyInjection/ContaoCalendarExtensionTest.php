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
use Contao\CalendarBundle\EventListener\GeneratePageListener;
use Contao\CalendarBundle\EventListener\InsertTagsListener;
use Contao\CalendarBundle\EventListener\PreviewUrlConvertListener;
use Contao\CalendarBundle\EventListener\PreviewUrlCreateListener;
use Contao\CalendarBundle\Menu\EventPickerProvider;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
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
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension = new ContaoCalendarExtension();
        $extension->load([], $this->container);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoCalendarExtension();

        $this->assertInstanceOf('Contao\CalendarBundle\DependencyInjection\ContaoCalendarExtension', $extension);
    }

    /**
     * Tests the contao_calendar.listener.generate_page service.
     */
    public function testGeneratePageListener()
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.generate_page'));

        $definition = $this->container->getDefinition('contao_calendar.listener.generate_page');

        $this->assertSame(GeneratePageListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao_calendar.listener.insert_tags service.
     */
    public function testInsertTagsListener()
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_calendar.listener.insert_tags');

        $this->assertSame(InsertTagsListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao_calendar.listener.preview_url_create service.
     */
    public function testPreviewUrlCreateListener()
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.preview_url_create'));

        $definition = $this->container->getDefinition('contao_calendar.listener.preview_url_create');

        $this->assertSame(PreviewUrlCreateListener::class, $definition->getClass());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('contao.preview_url_create', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onPreviewUrlCreate', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao_calendar.listener.preview_url_convert service.
     */
    public function testPreviewUrlConvertListener()
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.preview_url_convert'));

        $definition = $this->container->getDefinition('contao_calendar.listener.preview_url_convert');

        $this->assertSame(PreviewUrlConvertListener::class, $definition->getClass());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('contao.preview_url_convert', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onPreviewUrlConvert', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao_calendar.listener.event_picker_provider service.
     */
    public function testEventPickerProvider()
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.event_picker_provider'));

        $definition = $this->container->getDefinition('contao_calendar.listener.event_picker_provider');

        $this->assertSame(EventPickerProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('router', (string) $definition->getArgument(0));
        $this->assertSame('request_stack', (string) $definition->getArgument(1));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(2));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setFramework', $methodCalls[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_menu_provider', $tags);
        $this->assertSame(96, $tags['contao.picker_menu_provider'][0]['priority']);
    }
}
