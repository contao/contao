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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    public function testRegistersTheInsertTagsListener(): void
    {
        $this->assertTrue($this->container->has('contao_news.listener.insert_tags'));

        $definition = $this->container->getDefinition('contao_news.listener.insert_tags');

        $this->assertSame(InsertTagsListener::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    public function testRegistersThePreviewUrlCreateListener(): void
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

    public function testRegistersThePreviewUrlConvertListener(): void
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

    public function testRegistersTheNewsPickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao_news.picker.news_provider'));

        $definition = $this->container->getDefinition('contao_news.picker.news_provider');

        $this->assertSame(NewsPickerProvider::class, $definition->getClass());
        $this->assertSame('security.helper', (string) $definition->getArgument(0));
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(1));
        $this->assertSame('router', (string) $definition->getArgument(2));
        $this->assertSame('translator', (string) $definition->getArgument(3));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $this->assertSame('setFramework', $childDefinition->getMethodCalls()[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_provider', $tags);
        $this->assertSame(128, $tags['contao.picker_provider'][0]['priority']);
    }
}
