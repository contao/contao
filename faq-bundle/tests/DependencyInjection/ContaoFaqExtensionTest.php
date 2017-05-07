<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\FaqBundle\DependencyInjection\ContaoFaqExtension;
use Contao\FaqBundle\EventListener\InsertTagsListener;
use Contao\FaqBundle\Menu\FaqPickerProvider;
use Symfony\Component\DependencyInjection\ChildDefinition;
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

        $extension = new ContaoFaqExtension();
        $extension->load([], $this->container);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoFaqExtension();

        $this->assertInstanceOf('Contao\FaqBundle\DependencyInjection\ContaoFaqExtension', $extension);
    }

    /**
     * Tests the contao_faq.listener.insert_tags service.
     */
    public function testInsertTagsListener()
    {
        $this->assertTrue($this->container->has('contao_faq.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_faq.listener.insert_tags');

        $this->assertSame(InsertTagsListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao_faq.listener.faq_picker_provider service.
     */
    public function testFaqPickerProvider()
    {
        $this->assertTrue($this->container->has('contao_faq.listener.faq_picker_provider'));

        $definition = $this->container->getDefinition('contao_faq.listener.faq_picker_provider');

        $this->assertSame(FaqPickerProvider::class, $definition->getClass());
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
        $this->assertSame(64, $tags['contao.picker_menu_provider'][0]['priority']);
    }
}
