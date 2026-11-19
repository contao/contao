<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\DataContainer;

use Contao\ApiBundle\DataContainer\DataContainerFieldContext;
use Contao\ApiBundle\DataContainer\DataContainerWidgetFactory;
use Contao\DC_Table;
use Contao\SelectMenu;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Psr\Log\LoggerInterface;

final class DataContainerWidgetFactoryTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        System::setContainer($this->getContainerWithContaoConfiguration());

        $GLOBALS['BE_FFL']['select'] = SelectMenu::class;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_FFL']);
        $this->resetStaticProperties([System::class]);
        parent::tearDown();
    }

    public function testResolvesAttributesInTheCurrentFieldContext(): void
    {
        $dc = $this->createDataContainer();

        $field = new DataContainerFieldContext(
            [
                'inputType' => 'select',
                'eval' => ['mandatory' => true],
                'options_callback' => function (DC_Table $context) use ($dc): array {
                    $this->assertSame($dc, $context);
                    $this->assertSame('status', $context->field);

                    return ['live' => 'Published'];
                },
            ],
            'tl_test',
            'status',
        );

        $resolved = new DataContainerWidgetFactory()->resolve($field, $dc);
        $this->assertSame([['value' => 'live', 'label' => 'Published']], $resolved->config['eval']['options']);
        $this->assertTrue($resolved->config['eval']['mandatory']);
        $this->assertSame('tl_test', $resolved->table);
        $this->assertSame('status', $resolved->name);
        $this->assertSame('previous', $dc->field);
        $this->assertSame(['mandatory' => true], $field->config['eval']);
    }

    public function testRestoresTheFieldWhenACallbackThrows(): void
    {
        $dc = $this->createDataContainer();
        $exception = new \LogicException('Callback failed');

        $field = new DataContainerFieldContext(
            [
                'inputType' => 'select',
                'options_callback' => static fn () => throw $exception,
            ],
            'tl_test',
            'status',
        );

        $resolved = new DataContainerWidgetFactory()->resolve($field, $dc);

        $this->assertArrayNotHasKey('options', $resolved->config['eval']);

        $this->assertSame('previous', $dc->field);
    }

    public function testDocumentsAFieldWithoutOptionsWhenItsOptionsCallbackNeedsARecord(): void
    {
        $dc = $this->createDataContainer();
        $exception = new \LogicException('The options depend on the current record.');

        $field = new DataContainerFieldContext(
            [
                'inputType' => 'select',
                'eval' => ['mandatory' => true],
                'options_callback' => static fn () => throw $exception,
            ],
            'tl_test',
            'status',
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('The options_callback of field "tl_test.status" failed without a record, so the field is resolved without it.', ['exception' => $exception])
        ;

        $factory = new DataContainerWidgetFactory($logger);
        $factory->resolve($field, $dc);

        // The schema is built several times per request, but the failure is only logged once
        $resolved = $factory->resolve($field, $dc);

        $this->assertArrayNotHasKey('options_callback', $resolved->config);
        $this->assertArrayNotHasKey('options', $resolved->config['eval']);
        $this->assertTrue($resolved->config['eval']['mandatory']);
        $this->assertSame('previous', $dc->field);
    }

    public function testStillFailsWhenTheAttributesCannotBeResolvedWithoutTheOptionsCallback(): void
    {
        $dc = $this->createDataContainer();

        $field = new DataContainerFieldContext(
            [
                'inputType' => 'select',
                'foreignKey' => 'tl_page.title',
                'options_callback' => static fn () => throw new \LogicException('Callback failed'),
            ],
            'tl_test',
            'status',
        );

        try {
            new DataContainerWidgetFactory()->resolve($field, $dc);
            $this->fail('Expected a widget attribute resolution error.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('tl_test.status', $error->getMessage());
        }

        $this->assertSame('previous', $dc->field);
    }

    public function testCreatesARecordlessDataContainerForStaticCallbacks(): void
    {
        $field = new DataContainerFieldContext(
            [
                'inputType' => 'select',
                'options_callback' => function (DC_Table $context): array {
                    $this->assertSame('tl_test', $context->table);
                    $this->assertSame('status', $context->field);

                    return ['live' => 'Published'];
                },
            ],
            'tl_test',
            'status',
        );

        $resolved = new DataContainerWidgetFactory()->resolve($field);
        $this->assertSame([['value' => 'live', 'label' => 'Published']], $resolved->config['eval']['options']);
    }

    public function testLeavesFieldsWithoutAWidgetUnchanged(): void
    {
        $dc = $this->createDataContainer();
        $factory = new DataContainerWidgetFactory();
        $GLOBALS['BE_FFL']['notAWidget'] = \stdClass::class;

        foreach ([[], ['inputType' => 'missing'], ['inputType' => 'notAWidget']] as $config) {
            $field = new DataContainerFieldContext($config, 'tl_test', 'status');
            $this->assertSame($field, $factory->resolve($field, $dc));
        }

        $this->assertSame('previous', $dc->field);
    }

    private function createDataContainer(): DC_Table
    {
        $dc = new \ReflectionClass(DC_Table::class)->newInstanceWithoutConstructor();
        new \ReflectionProperty(DC_Table::class, 'strTable')->setValue($dc, 'tl_test');
        $dc->field = 'previous';

        return $dc;
    }
}
