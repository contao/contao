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

use Contao\ApiBundle\ContaoApiBundle;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Api\Widget\RowWizardConverter;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Password;
use Contao\RowWizard;
use Contao\TextField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoApiExtensionTest extends TestCase
{
    public function testLoadsServicesAndParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        new ContaoApiBundle()->getContainerExtension()->load([['api_prefix' => '/custom-api']], $container);

        $this->assertTrue($container->hasDefinition(DataContainerSchemaFactory::class));
        $this->assertTrue($container->hasDefinition(WidgetConverterRegistry::class));
        $this->assertSame('/custom-api', $container->getParameter('contao_api.api_prefix'));
    }

    public function testAutoconfiguresConvertersBeforeTheCoreFallback(): void
    {
        $container = $this->createConverterContainer();

        $converter = $this->createMock(WidgetConverterInterface::class);
        $converter
            ->expects($this->exactly(2))
            ->method('supports')
            ->willReturnCallback(static fn (array $config): bool => 'custom' === ($config['inputType'] ?? null))
        ;
        $container->register($converter::class, $converter::class)->setAutoconfigured(true)->setSynthetic(true)->setPublic(true);
        $container->getDefinition(WidgetConverterRegistry::class)->setPublic(true);
        $container->compile();
        $container->set($converter::class, $converter);

        $registry = $container->get(WidgetConverterRegistry::class);

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
        $container->getDefinition(WidgetConverterRegistry::class)->setPublic(true);
        $container->compile();
        $widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL']['rows'] = RowWizard::class;
        $GLOBALS['BE_FFL']['text'] = TextField::class;

        try {
            $registry = $container->get(WidgetConverterRegistry::class);
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
        $container->getDefinition(WidgetConverterRegistry::class)->setPublic(true);
        $container->getDefinition(DataContainerSchemaFactory::class)->setPublic(true);
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
            $this->assertNull($container->get(WidgetConverterRegistry::class)->get($unsupported));
            $factory = $container->get(DataContainerSchemaFactory::class);
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

        $extension = new ContaoApiBundle()->getContainerExtension();
        $container->registerExtension($extension);
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.default_locale', 'en');
        new ContaoCoreExtension()->load([], $container);
        $extension->load([], $container);

        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!\in_array($id, ['service_container', WidgetConverterRegistry::class, 'contao.api.widget_converter', 'contao.api.widget.row_wizard_converter', DataContainerSchemaFactory::class, 'contao.widget.date_value_formatter'], true)) {
                $container->removeDefinition($id);
            }
        }

        foreach (array_keys($container->getAliases()) as $id) {
            $container->removeAlias($id);
        }

        $container->register('contao.framework', ContaoFramework::class)->setSynthetic(true)->setPublic(true);
        $container->set('contao.framework', $this->createStub(ContaoFramework::class));

        return $container;
    }
}
