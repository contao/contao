<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\FaqBundle\DependencyInjection\ContaoFaqExtension;
use Contao\FaqBundle\EventListener\InsertTagsListener;
use Contao\FaqBundle\Picker\FaqPickerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ContaoFaqExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension = new ContaoFaqExtension();
        $extension->load([], $this->container);
    }

    public function testRegistersTheInsertTagsListener(): void
    {
        $this->assertTrue($this->container->has('contao_faq.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_faq.listener.insert_tags');

        $this->assertSame(InsertTagsListener::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame(ContaoFramework::class, (string) $definition->getArgument(0));
    }

    public function testRegistersTheEventPickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao_faq.picker.faq_provider'));

        $definition = $this->container->getDefinition('contao_faq.picker.faq_provider');

        $this->assertSame(FaqPickerProvider::class, $definition->getClass());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('translator', (string) $definition->getArgument(2));
        $this->assertSame('security.helper', (string) $definition->getArgument(3));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $this->assertSame('setFramework', $childDefinition->getMethodCalls()[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_provider', $tags);
        $this->assertSame(64, $tags['contao.picker_provider'][0]['priority']);
    }
}
