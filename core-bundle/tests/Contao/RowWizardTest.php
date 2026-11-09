<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Input;
use Contao\RowWizard;
use Contao\System;
use Contao\TextField;
use Contao\Widget;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class RowWizardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->set('request_stack', new RequestStack());
        $container->set('contao.routing.scope_matcher', $this->createStub(ScopeMatcher::class));
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        System::setContainer($container);

        $GLOBALS['BE_FFL']['text'] = TextField::class;
        $GLOBALS['TL_LANG']['ERR']['general'] = 'Invalid row';
        $GLOBALS['TL_LANG']['ERR']['mandatory'] = 'Mandatory field %s';
        $GLOBALS['TL_LANG']['ERR']['mdtryNoLabel'] = 'Mandatory field';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_FFL'], $GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([Input::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testChainsSaveCallbacksForEachRow(): void
    {
        $dataContainer = $this->createStub(DataContainer::class);
        $callback = new class() {
            public array $calls = [];

            public function save(string $value, DataContainer $dc): string
            {
                $this->calls[] = [$value, $dc];

                return strtoupper($value);
            }
        };

        System::getContainer()->set('row_wizard.callback', $callback);

        $wizard = $this->createWizard(
            [
                'name' => [
                    'inputType' => 'text',
                    'save_callback' => [
                        ['row_wizard.callback', 'save'],
                        static fn (string $value, DataContainer $dc) => $value.'-'.$dc::class,
                    ],
                ],
                'unchanged' => ['inputType' => 'text'],
            ],
            [
                ['name' => 'First', 'unchanged' => 'one'],
                ['name' => 'Second', 'unchanged' => 'two'],
            ],
        );
        $wizard->dataContainer = $dataContainer;

        $wizard->validate();

        $this->assertFalse($wizard->hasErrors());
        $this->assertSame([['First', $dataContainer], ['Second', $dataContainer]], $callback->calls);
        $this->assertSame(
            [
                ['name' => 'FIRST-'.$dataContainer::class, 'unchanged' => 'one'],
                ['name' => 'SECOND-'.$dataContainer::class, 'unchanged' => 'two'],
            ],
            $wizard->value,
        );
    }

    public function testChainsLoadCallbacksForEachRowAndCachesTheWidgets(): void
    {
        $dataContainer = $this->createStub(DataContainer::class);
        $callback = new class() {
            public array $calls = [];

            public function load(string $value, DataContainer $dc): string
            {
                $this->calls[] = [$value, $dc];

                return strtoupper($value);
            }
        };

        System::getContainer()->set('row_wizard.callback', $callback);

        $fields = [
            'name' => [
                'inputType' => 'text',
                'load_callback' => [
                    ['row_wizard.callback', 'load'],
                    static fn (string $value, DataContainer $dc) => $value.'-'.$dc::class,
                ],
            ],
            'unchanged' => ['inputType' => 'text'],
        ];
        $rows = [
            ['name' => 'First', 'unchanged' => 'one'],
            ['name' => 'Second', 'unchanged' => 'two'],
        ];
        $wizard = $this->createWizard($fields, $rows);
        $wizard->dataContainer = $dataContainer;
        $wizard->value = $rows;
        $prepareWidget = new \ReflectionMethod(RowWizard::class, 'prepareWidget');

        foreach ($rows as $i => $row) {
            foreach ($fields as $key => $options) {
                [$widget, $data] = $prepareWidget->invoke($wizard, $key, $row[$key], $options, $i);
                $expected = 'name' === $key ? strtoupper($row[$key]).'-'.$dataContainer::class : $row[$key];

                $this->assertSame($expected, $widget->value);
                $this->assertSame($expected, $data['value']);
                $this->assertSame([$widget, $data], $prepareWidget->invoke($wizard, $key, $row[$key], $options, $i));
            }
        }

        $this->assertSame([['First', $dataContainer], ['Second', $dataContainer]], $callback->calls);
        $this->assertSame($rows, $wizard->value);

        $wizard->validate();

        $this->assertSame($rows, $wizard->value);
        $this->assertSame([['First', $dataContainer], ['Second', $dataContainer]], $callback->calls);
    }

    public function testLoadCallbacksRunBeforeDeserializingTheValue(): void
    {
        $stored = serialize(['stored']);
        $fields = [
            'name' => [
                'inputType' => 'text',
                'load_callback' => [static function ($value, $dc) use ($stored) {
                    self::assertSame($stored, $value);
                    self::assertNull($dc);

                    return serialize(['loaded']);
                }],
            ],
        ];
        $wizard = $this->createWizard($fields, []);
        $prepareWidget = new \ReflectionMethod(RowWizard::class, 'prepareWidget');
        [$widget, $data] = $prepareWidget->invoke($wizard, 'name', $stored, $fields['name'], 0);

        $this->assertSame(['loaded'], $widget->value);
        $this->assertSame(['loaded'], $data['value']);
    }

    public function testLoadCallbackExceptionsPropagate(): void
    {
        $fields = [
            'name' => [
                'inputType' => 'text',
                'load_callback' => [static function () {
                    throw new \RuntimeException('Cannot load name');
                }],
            ],
        ];
        $wizard = $this->createWizard($fields, []);
        $prepareWidget = new \ReflectionMethod(RowWizard::class, 'prepareWidget');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot load name');

        $prepareWidget->invoke($wizard, 'name', null, $fields['name'], 0);
    }

    public function testCallbackExceptionsBecomeNestedWidgetErrors(): void
    {
        $wizard = $this->createWizard(
            [
                'name' => [
                    'inputType' => 'text',
                    'save_callback' => [static function () {
                        throw new \RuntimeException('Invalid name');
                    }],
                ],
                'unchanged' => ['inputType' => 'text'],
            ],
            [['name' => 'Keep', 'unchanged' => 'one']],
        );

        $wizard->validate();

        $this->assertTrue($wizard->hasErrors());
        $this->assertSame('error', $wizard->class);
        $this->assertSame([['unchanged' => 'one']], $wizard->value);

        $widgets = (new \ReflectionProperty(RowWizard::class, 'widgets'))->getValue($wizard);

        $this->assertSame(['Invalid name'], $widgets[0]['name'][0]->getErrors());
        $this->assertSame('Keep', $widgets[0]['name'][0]->value);
    }

    public function testDoesNotCallSaveCallbacksForInvalidValues(): void
    {
        $wizard = $this->createWizard(
            [
                'name' => [
                    'inputType' => 'text',
                    'eval' => ['mandatory' => true],
                    'save_callback' => [static function () {
                        self::fail('Save callbacks must not run for invalid values.');
                    }],
                ],
            ],
            [['name' => '']],
        );

        $wizard->validate();

        $this->assertTrue($wizard->hasErrors());
    }

    public function testResponseExceptionsPropagate(): void
    {
        $exception = new ResponseException(new Response());
        $wizard = $this->createWizard(
            [
                'name' => [
                    'inputType' => 'text',
                    'save_callback' => [static function () use ($exception) {
                        throw $exception;
                    }],
                ],
            ],
            [['name' => 'First']],
        );

        $this->expectExceptionObject($exception);

        $wizard->validate();
    }

    private function createWizard(array $fields, array $rows): RowWizard
    {
        System::getContainer()->get('request_stack')->push(new Request([], [
            'rows' => ['_rows' => array_fill(0, \count($rows), '')] + $rows,
        ]));

        return new class(['id' => 'rows', 'fields' => $fields]) extends RowWizard {
            public function __construct(array $attributes)
            {
                Widget::__construct($attributes);
            }
        };
    }
}
