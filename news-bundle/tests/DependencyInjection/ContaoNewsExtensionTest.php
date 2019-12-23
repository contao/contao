<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\NewsBundle\DependencyInjection\ContaoNewsExtension;
use Contao\NewsBundle\EventListener\GeneratePageListener;
use Contao\NewsBundle\EventListener\InsertTagsListener;
use Contao\NewsBundle\EventListener\PreviewUrlConvertListener;
use Contao\NewsBundle\EventListener\PreviewUrlCreateListener;
use Contao\NewsBundle\Picker\NewsPickerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class ContaoNewsExtensionTest extends TestCase
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

        $extension = new ContaoNewsExtension();
        $extension->load([], $this->container);
    }

    public function testRegistersTheGeneratePageListener(): void
    {
        $this->assertTrue($this->container->has('contao_news.listener.generate_page'));

        $definition = $this->container->getDefinition('contao_news.listener.generate_page');

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
        $this->assertTrue($this->container->has('contao_news.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_news.listener.insert_tags');

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
        $this->assertTrue($this->container->has('contao_news.listener.preview_url_create'));

        $definition = $this->container->getDefinition('contao_news.listener.preview_url_create');

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
                    [
                        'event' => 'contao.preview_url_create',
                        'method' => 'onPreviewUrlCreate',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersThePreviewUrlConvertListener(): void
    {
        $this->assertTrue($this->container->has('contao_news.listener.preview_url_convert'));

        $definition = $this->container->getDefinition('contao_news.listener.preview_url_convert');

        $this->assertSame(PreviewUrlConvertListener::class, $definition->getClass());
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
                    [
                        'event' => 'contao.preview_url_convert',
                        'method' => 'onPreviewUrlConvert',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheNewsPickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao_news.picker.news_provider'));

        $definition = $this->container->getDefinition('contao_news.picker.news_provider');

        $this->assertSame(NewsPickerProvider::class, $definition->getClass());
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
                        'priority' => 128,
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
