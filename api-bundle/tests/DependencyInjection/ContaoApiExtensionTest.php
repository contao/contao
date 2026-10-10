<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\DependencyInjection;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\ContaoApiBundle;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Api\Widget\RowWizardConverter;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Password;
use Contao\RowWizard;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveNamedArgumentsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\LocaleSwitcher;

class ContaoApiExtensionTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testApiPrefixFollowsTheBackendPrefix(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $container->setParameter('contao.backend.route_prefix', '/admin');

        new ContaoApiBundle()->getContainerExtension()->load([], $container);

        $this->assertSame('/admin/api', $container->getParameterBag()->resolveValue($container->getDefinition('contao_api.api_platform.data_container_open_api_factory')->getArgument('$apiPrefix')));
    }

    public function testLoadsServices(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        new ContaoApiBundle()->getContainerExtension()->load([], $container);

        $this->assertTrue($container->hasDefinition('contao_api.schema.data_container_factory'));
        $this->assertTrue($container->hasDefinition('contao_api.widget.converter_registry'));
    }

    public function testResolvesTemplateMetadataFactoryArguments(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        new ContaoApiBundle()->getContainerExtension()->load([], $container);
        new ResolveClassPass()->process($container);
        new ResolveNamedArgumentsPass()->process($container);

        $operations = $container->getDefinition('contao_api.api_platform.user_template_resource_metadata_collection_factory')->getArgument(1);
        $this->assertInstanceOf(TaggedIteratorArgument::class, $operations);
        $this->assertSame('contao.operation.template_studio_element', $operations->getTag());
    }

    public function testAutoconfiguresConvertersBeforeTheCoreFallback(): void
    {
        $converter = $this->createMock(WidgetConverterInterface::class);
        $converter
            ->expects($this->exactly(2))
            ->method('supports')
            ->willReturnCallback(static fn (array $config): bool => 'custom' === ($config['inputType'] ?? null))
        ;

        $container = $this->createConverterContainer();
        $container->register($converter::class, $converter::class)->setAutoconfigured(true)->setSynthetic(true)->setPublic(true);
        $container->getDefinition('contao_api.widget.converter_registry')->setPublic(true);
        $container->compile();
        $container->set($converter::class, $converter);

        $registry = $container->get('contao_api.widget.converter_registry');

        $widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL']['custom'] = TextField::class;
        $GLOBALS['BE_FFL']['password'] = Password::class;

        try {
            $this->assertSame($converter, $registry->get(['inputType' => 'custom']));
            $this->assertInstanceOf(CoreWidgetConverter::class, $registry->get(['inputType' => 'password']));
        } finally {
            unset($GLOBALS['BE_FFL']);

            if (null !== $widgets) {
                $GLOBALS['BE_FFL'] = $widgets;
            }
        }
    }

    public function testResolvesTheRowConverterThroughTheRegistry(): void
    {
        $container = $this->createConverterContainer();
        $container->getDefinition('contao_api.widget.converter_registry')->setPublic(true);
        $container->compile();

        $widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL']['rows'] = RowWizard::class;
        $GLOBALS['BE_FFL']['text'] = TextField::class;

        try {
            $registry = $container->get('contao_api.widget.converter_registry');
            $config = ['inputType' => 'rows', 'fields' => ['title' => ['inputType' => 'text']]];
            $converter = $registry->get($config);

            $this->assertInstanceOf(RowWizardConverter::class, $converter);
            $this->assertSame('string', $converter->getSchema($config, [])['items']['properties']['title']['type']);
        } finally {
            unset($GLOBALS['BE_FFL']);

            if (null !== $widgets) {
                $GLOBALS['BE_FFL'] = $widgets;
            }
        }
    }

    public function testUnsupportedRowChildrenExcludeTheEntireField(): void
    {
        $container = $this->createConverterContainer();
        $container->getDefinition('contao_api.widget.converter_registry')->setPublic(true);
        $container->getDefinition('contao_api.schema.data_container_factory')->setPublic(true);
        $container->compile();

        $widgets = $GLOBALS['BE_FFL'] ?? null;
        $dca = $GLOBALS['TL_DCA'] ?? null;
        $GLOBALS['BE_FFL']['rows'] = RowWizard::class;
        $GLOBALS['BE_FFL']['text'] = TextField::class;

        $unsupported = ['inputType' => 'rows', 'fields' => [
            'title' => ['inputType' => 'text'],
            'unknown' => ['inputType' => 'unknown'],
        ]];

        $GLOBALS['TL_DCA']['tl_test']['fields'] = [
            'title' => ['inputType' => 'text'],
            'rows' => $unsupported,
            'nested' => ['inputType' => 'rows', 'fields' => ['child' => $unsupported]],
        ];

        try {
            $this->assertNull($container->get('contao_api.widget.converter_registry')->get($unsupported));
            $factory = $container->get('contao_api.schema.data_container_factory');
            $this->assertSame(['title'], array_keys($factory->create('tl_test')['properties']));

            foreach (['read', 'create', 'update'] as $operation) {
                $this->assertSame(['title'], array_keys($factory->createOperationSchemas('tl_test')[$operation]['properties']));
            }
        } finally {
            unset($GLOBALS['BE_FFL'], $GLOBALS['TL_DCA']);

            if (null !== $widgets) {
                $GLOBALS['BE_FFL'] = $widgets;
            }

            if (null !== $dca) {
                $GLOBALS['TL_DCA'] = $dca;
            }
        }
    }

    private function createConverterContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('kernel.bundles', ['ContaoApiBundle' => ContaoApiBundle::class]);

        $extension = new ContaoApiBundle()->getContainerExtension();
        $container->registerExtension($extension);

        new ContaoCoreExtension()->load([], $container);
        $extension->load([], $container);

        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!\in_array($id, ['service_container', 'contao_api.widget.converter_registry', 'contao.api.widget_converter', 'contao.api.widget.row_wizard_converter', 'contao_api.schema.data_container_factory', 'contao.widget.date_value_formatter'], true)) {
                $container->removeDefinition($id);
            }
        }

        foreach (array_keys($container->getAliases()) as $id) {
            $container->removeAlias($id);
        }

        $container->register('contao.framework', ContaoFramework::class)->setSynthetic(true)->setPublic(true);
        $container->set('contao.framework', $this->createStub(ContaoFramework::class));
        $container->register('contao_api.data_container.relation_resolver', DataContainerRelationResolver::class)->setSynthetic(true);
        $container->set('contao_api.data_container.relation_resolver', $this->createRelationResolver());
        $container->register('translation.locale_switcher', LocaleSwitcher::class)->setSynthetic(true);
        $container->set('translation.locale_switcher', $this->createLocaleSwitcher());

        System::setContainer($container);

        return $container;
    }

    private function createRelationResolver(): DataContainerRelationResolver
    {
        $connection = $this->createStub(Connection::class);

        return new DataContainerRelationResolver(
            $connection,
            new ForeignKeyParser($connection),
            new WidgetConverterRegistry([]),
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $this->createStub(RouterInterface::class),
            $this->createStub(DcaHierarchy::class),
        );
    }

    private function createLocaleSwitcher(): LocaleSwitcher
    {
        $localeSwitcher = $this->createStub(LocaleSwitcher::class);
        $localeSwitcher
            ->method('runWithLocale')
            ->willReturnCallback(static fn (string $locale, callable $callback): mixed => $callback($locale))
        ;

        return $localeSwitcher;
    }
}
