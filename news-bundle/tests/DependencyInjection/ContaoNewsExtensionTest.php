<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\NewsBundle\DependencyInjection\ContaoNewsExtension;
use Contao\NewsBundle\EventListener\GeneratePageListener;
use Contao\NewsBundle\EventListener\InsertTagsListener;
use Contao\NewsBundle\EventListener\PreviewUrlConvertListener;
use Contao\NewsBundle\EventListener\PreviewUrlCreateListener;
use Contao\NewsBundle\Menu\NewsPickerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the ContaoNewsExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoNewsExtensionTest extends TestCase
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

        $extension = new ContaoNewsExtension();
        $extension->load([], $this->container);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoNewsExtension();

        $this->assertInstanceOf('Contao\NewsBundle\DependencyInjection\ContaoNewsExtension', $extension);
    }

    /**
     * Tests the contao_news.listener.generate_page service.
     */
    public function testGeneratePageListener()
    {
        $this->assertTrue($this->container->has('contao_news.listener.generate_page'));

        $definition = $this->container->getDefinition('contao_news.listener.generate_page');

        $this->assertSame(GeneratePageListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao_news.listener.insert_tags service.
     */
    public function testInsertTagsListener()
    {
        $this->assertTrue($this->container->has('contao_news.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_news.listener.insert_tags');

        $this->assertSame(InsertTagsListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao_news.listener.preview_url_create service.
     */
    public function testPreviewUrlCreateListener()
    {
        $this->assertTrue($this->container->has('contao_news.listener.preview_url_create'));

        $definition = $this->container->getDefinition('contao_news.listener.preview_url_create');

        $this->assertSame(PreviewUrlCreateListener::class, $definition->getClass());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('contao.preview_url_create', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onPreviewUrlCreate', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao_news.listener.preview_url_convert service.
     */
    public function testPreviewUrlConvertListener()
    {
        $this->assertTrue($this->container->has('contao_news.listener.preview_url_convert'));

        $definition = $this->container->getDefinition('contao_news.listener.preview_url_convert');

        $this->assertSame(PreviewUrlConvertListener::class, $definition->getClass());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('contao.preview_url_convert', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onPreviewUrlConvert', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao_news.listener.news_picker_provider service.
     */
    public function testNewsPickerProvider()
    {
        $this->assertTrue($this->container->has('contao_news.listener.news_picker_provider'));

        $definition = $this->container->getDefinition('contao_news.listener.news_picker_provider');

        $this->assertSame(NewsPickerProvider::class, $definition->getClass());
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
        $this->assertSame(128, $tags['contao.picker_menu_provider'][0]['priority']);
    }
}
