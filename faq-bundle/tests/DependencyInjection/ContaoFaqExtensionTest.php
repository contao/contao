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

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\FaqBundle\DependencyInjection\ContaoFaqExtension;
use Contao\FaqBundle\EventListener\InsertTagsListener;
use Contao\FaqBundle\Picker\FaqPickerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

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

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.hook' => [
                    [
                        'hook' => 'replaceInsertTags',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheEventPickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao_faq.picker.faq_provider'));

        $definition = $this->container->getDefinition('contao_faq.picker.faq_provider');

        $this->assertSame(FaqPickerProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('knp_menu.factory'),
                new Reference('router'),
                new Reference('translator', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                new Reference('security.helper'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.picker_provider' => [
                    [
                        'priority' => 64,
                    ],
                ],
            ],
            $definition->getTags()
        );

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $this->assertEquals(
            [
                [
                    'setFramework',
                    [new Reference('contao.framework')],
                ],
            ],
            $childDefinition->getMethodCalls()
        );
    }
}
