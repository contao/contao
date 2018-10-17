<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\FaqBundle\DependencyInjection\ContaoFaqExtension;
use Contao\FaqBundle\EventListener\InsertTagsListener;
use Contao\FaqBundle\Picker\FaqPickerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the ContaoFaqExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoFaqExtensionTest extends TestCase
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
     * Tests the contao_faq.listener.insert_tags service.
     */
    public function testRegistersTheInsertTagsListener()
    {
        $this->assertTrue($this->container->has('contao_faq.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_faq.listener.insert_tags');

        $this->assertSame(InsertTagsListener::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao_faq.picker.faq_provider service.
     */
    public function testRegistersTheEventPickerProvider()
    {
        $this->assertTrue($this->container->has('contao_faq.picker.faq_provider'));

        $definition = $this->container->getDefinition('contao_faq.picker.faq_provider');

        $this->assertSame(FaqPickerProvider::class, $definition->getClass());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $this->assertSame('setFramework', $childDefinition->getMethodCalls()[0][0]);

        $tags = $definition->getTags();

        $this->assertSame('setTokenStorage', $definition->getMethodCalls()[0][0]);

        $this->assertArrayHasKey('contao.picker_provider', $tags);
        $this->assertSame(64, $tags['contao.picker_provider'][0]['priority']);
    }
}
