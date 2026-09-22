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
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Password;
use Contao\TextField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoApiExtensionTest extends TestCase
{
    public function testLoadsServicesAndParameters(): void
    {
        $container = new ContainerBuilder();
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

    private function createConverterContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new ContaoApiBundle()->getContainerExtension();
        $container->registerExtension($extension);
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.default_locale', 'en');
        new ContaoCoreExtension()->load([], $container);
        $extension->load([], $container);

        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!\in_array($id, ['service_container', WidgetConverterRegistry::class, 'contao.api.widget_converter', 'contao.widget.date_value_formatter'], true)) {
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
