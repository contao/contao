<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\DependencyInjection;

use Contao\CalendarBundle\DependencyInjection\ContaoCalendarExtension;
use Contao\CalendarBundle\EventListener\GeneratePageListener;
use Contao\CalendarBundle\EventListener\InsertTagsListener;
use Contao\CalendarBundle\EventListener\PreviewUrlConvertListener;
use Contao\CalendarBundle\EventListener\PreviewUrlCreateListener;
use Contao\CalendarBundle\Picker\EventPickerProvider;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class ContaoCalendarExtensionTest extends TestCase
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

        $extension = new ContaoCalendarExtension();
        $extension->load([], $this->container);
    }

    public function testRegistersTheGeneratePageListener(): void
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.generate_page'));

        $definition = $this->container->getDefinition('contao_calendar.listener.generate_page');

        $this->assertSame(GeneratePageListener::class, $definition->getClass());
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
                        'hook' => 'generatePage',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheInsertTagsListener(): void
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_calendar.listener.insert_tags');

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

    public function testRegistersThePreviewUrlCreateListener(): void
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.preview_url_create'));

        $definition = $this->container->getDefinition('contao_calendar.listener.preview_url_create');

        $this->assertSame(PreviewUrlCreateListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersThePreviewUrlConvertListener(): void
    {
        $this->assertTrue($this->container->has('contao_calendar.listener.preview_url_convert'));

        $definition = $this->container->getDefinition('contao_calendar.listener.preview_url_convert');

        $this->assertSame(PreviewUrlConvertListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheEventPickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao_calendar.picker.event_provider'));

        $definition = $this->container->getDefinition('contao_calendar.picker.event_provider');

        $this->assertSame(EventPickerProvider::class, $definition->getClass());
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
                        'priority' => 96,
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
