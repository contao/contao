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

use Contao\ApiBundle\DataContainer\DataContainerRecordMapper;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CheckBox;
use Contao\Controller;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\Date;
use Contao\FileTree;
use Contao\PageTree;
use Contao\Password;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;
use Contao\Widget;
use Opis\JsonSchema\Validator as JsonSchemaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class DataContainerRecordMapperTest extends ContaoTestCase
{
    private array|null $widgets = null;

    private WidgetConverterRegistry $converters;

    protected function setUp(): void
    {
        parent::setUp();

        $framework = $this->createStub(ContaoFramework::class);
        $framework
            ->method('getAdapter')
            ->willReturn(new Adapter(Date::class))
        ;

        $framework
            ->method('createInstance')
            ->willReturnCallback(static fn (string $class, array $arguments): Date => new Date(...$arguments))
        ;

        $this->converters = new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($framework))]);
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;

        $GLOBALS['BE_FFL'] = [
            'text' => TextField::class,
            'custom' => TextField::class,
            'checkbox' => CheckBox::class,
            'fileTree' => FileTree::class,
            'pageTree' => PageTree::class,
            'password' => Password::class,
        ];
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }

        parent::tearDown();
    }

    public function testConvertsStoredValuesWithoutExposingPasswordsOrUnknownColumns(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => ['sql' => ['type' => 'integer']],
            'title' => ['inputType' => 'text', 'sql' => ['type' => 'string']],
            'published' => ['inputType' => 'checkbox'],
            'count' => ['inputType' => 'text', 'sql' => ['type' => 'integer']],
            'tags' => ['inputType' => 'text', 'sql' => ['type' => 'string'], 'eval' => ['multiple' => true]],
            'password' => ['inputType' => 'password', 'sql' => ['type' => 'string']],
        ];

        $controller = $this->createAdapterMock(['loadDataContainer']);
        $controller
            ->expects($this->once())
            ->method('loadDataContainer')
            ->with('tl_content')
        ;

        $framework = $this->createContaoFrameworkMock([Controller::class => $controller]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters), $this->converters);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'title' => 'Example', 'published' => '1', 'count' => '42', 'tags' => serialize(['one', 'two']), 'password' => 'hash', 'unknown' => 'private']);

        $this->assertSame(17, $record->id);
        $this->assertSame(['title' => 'Example', 'published' => true, 'count' => 42, 'tags' => ['one', 'two']], $record->data);
    }

    public function testReadsRecordMetadataWithoutAWidget(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => ['sql' => ['type' => 'integer']],
            'tstamp' => ['sql' => ['type' => 'integer']],
            'pid' => ['sql' => ['type' => 'integer']],
            'ptable' => ['sql' => ['type' => 'string']],
            'sorting' => ['sql' => ['type' => 'integer']],
            'internal' => ['sql' => ['type' => 'string']],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($this->createContaoFrameworkStub([Controller::class => $controller]), $this->converters), $this->converters);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'tstamp' => '123', 'pid' => '42', 'ptable' => 'tl_article', 'sorting' => '128', 'internal' => 'hidden']);

        $this->assertSame(17, $record->id);
        $this->assertSame(['tstamp' => 123, 'pid' => 42, 'ptable' => 'tl_article', 'sorting' => 128], $record->data);
    }

    public function testExposesFileReferencesAsUuids(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['singleSRC'] = ['inputType' => 'fileTree', 'sql' => ['type' => 'binary', 'length' => 16]];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $framework = $this->createContaoFrameworkStub([Controller::class => $controller]);
        $factory = new DataContainerSchemaFactory($framework, $this->converters);
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $mapper = new DataContainerRecordMapper($factory, $this->converters);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'singleSRC' => hex2bin(str_replace('-', '', $uuid))]);

        $this->assertSame($uuid, $record->data['singleSRC']);
        $this->assertSame('uuid', $factory->create('tl_content')['properties']['singleSRC']['format']);
        $this->assertArrayNotHasKey('maxLength', $factory->create('tl_content')['properties']['singleSRC']);
    }

    #[DataProvider('provideEmptyValues')]
    public function testEmptyValuesMatchTheWidgetSchema(array $config, mixed $stored, mixed $expected): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['value'] = $config;

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $factory = new DataContainerSchemaFactory($this->createContaoFrameworkStub([Controller::class => $controller]), $this->converters);

        $mapper = new DataContainerRecordMapper($factory, $this->converters);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'value' => $stored]);

        $schema = json_decode(json_encode($factory->create('tl_content'), JSON_THROW_ON_ERROR), null, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($expected, $record->data['value']);
        $this->assertTrue(new JsonSchemaValidator()->validate((object) $record->data, $schema)->isValid());
        $this->assertSame(['value' => \is_array($expected) && 'text' === $config['inputType'] ? [] : ''], $mapper->toFormValues('tl_content', ['value' => $expected]));
    }

    public static function provideEmptyValues(): iterable
    {
        yield 'null file' => [['inputType' => 'fileTree'], null, null];
        yield 'empty file' => [['inputType' => 'fileTree'], '', null];
        yield 'null files' => [['inputType' => 'fileTree', 'eval' => ['multiple' => true]], null, []];
        yield 'empty files' => [['inputType' => 'fileTree', 'eval' => ['multiple' => true]], '', []];
        yield 'null text' => [['inputType' => 'text', 'sql' => ['type' => 'text']], null, ''];
        yield 'null multiple text' => [['inputType' => 'text', 'eval' => ['multiple' => true]], null, []];
        yield 'null checkbox' => [['inputType' => 'checkbox'], null, false];
    }

    public function testUsesCustomSchemaForVisibilityAndStorageConversion(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'secret' => ['inputType' => 'custom', 'api' => ['schema' => ['type' => 'string', 'writeOnly' => true]]],
            'references' => ['inputType' => 'fileTree', 'eval' => ['multiple' => true]],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $framework = $this->createContaoFrameworkStub([Controller::class => $controller]);
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters), $this->converters);

        $record = $mapper->fromRow('tl_content', [
            'id' => 17,
            'secret' => 'hidden',
            'references' => serialize([hex2bin(str_replace('-', '', $uuid))]),
        ]);

        $this->assertSame(['references' => [$uuid]], $record->data);
    }

    public function testDecodesArrayStorageFromDca(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'json' => ['inputType' => 'text', 'sql' => ['type' => 'json']],
            'csv' => ['inputType' => 'text', 'eval' => ['multiple' => true, 'csv' => '|']],
            'serialized' => ['inputType' => 'text', 'eval' => ['multiple' => true]],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $factory = new DataContainerSchemaFactory($this->createContaoFrameworkStub([Controller::class => $controller]), $this->converters);

        $mapper = new DataContainerRecordMapper($factory, $this->converters);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'json' => '["one","two"]', 'csv' => 'one|two', 'serialized' => serialize(['one', 'two'])]);

        $this->assertSame(['json' => ['one', 'two'], 'csv' => ['one', 'two'], 'serialized' => ['one', 'two']], $record->data);
        $this->assertSame(['type' => 'array'], $factory->create('tl_content')['properties']['json']);
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'string']], $factory->create('tl_content')['properties']['csv']);
    }

    public function testInheritsWidgetConversionIndependentlyOfSchema(): void
    {
        $widget = new class() extends FileTree {
            public function __construct()
            {
            }
        };

        $GLOBALS['BE_FFL']['customFiles'] = $widget::class;

        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'files' => ['inputType' => 'customFiles', 'eval' => ['multiple' => true]],
            'textual' => ['inputType' => 'customFiles', 'eval' => ['binary' => false]],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($this->createContaoFrameworkStub([Controller::class => $controller]), $this->converters), $this->converters);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'files' => serialize([hex2bin(str_replace('-', '', $uuid))]), 'textual' => '1234567890123456']);

        $this->assertSame(['files' => [$uuid], 'textual' => '1234567890123456'], $record->data);
        $this->assertSame(['files' => $uuid.','.$uuid], $mapper->toFormValues('tl_content', ['files' => [$uuid, $uuid]]));
        $this->assertSame(['files' => ''], $mapper->toFormValues('tl_content', ['files' => []]));
    }

    public function testConvertsFormValuesThroughTheWidget(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'pages' => ['inputType' => 'pageTree', 'eval' => ['multiple' => true]],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $framework = $this->createContaoFrameworkStub([Controller::class => $controller]);

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters), $this->converters);

        $this->assertSame(['pages' => '1,2'], $mapper->toFormValues('tl_content', ['pages' => [1, 2]]));
    }

    public function testFormatsTimestampsUsingDcaWithoutExposingFormMetadata(): void
    {
        $container = new ContainerBuilder();
        $container->set('contao.routing.page_finder', $this->createStub(PageFinder::class));
        System::setContainer($container);

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $framework = $this->createContaoFrameworkStub([Controller::class => $controller]);
        $factory = new DataContainerSchemaFactory($framework, $this->converters);
        $mapper = new DataContainerRecordMapper($factory, $this->converters);
        $timestamp = mktime(14, 35, 0, 9, 17, 2026);

        foreach (['date' => ['d.m.Y', '17.09.2026'], 'time' => ['H:i', '14:35'], 'datim' => ['d.m.Y H:i', '17.09.2026 14:35']] as $rgxp => [$format, $expected]) {
            $GLOBALS['TL_CONFIG'][$rgxp.'Format'] = $format;
            $GLOBALS['TL_DCA']['tl_content']['fields']['eventDate'] = ['inputType' => 'text', 'sql' => ['type' => 'integer'], 'eval' => ['rgxp' => $rgxp]];

            foreach (['read', 'create', 'update'] as $operation) {
                $this->assertSame(['type' => 'integer'], $factory->createOperationSchemas('tl_content')[$operation]['properties']['eventDate']);
            }

            $this->assertSame(['eventDate' => $timestamp], $mapper->fromRow('tl_content', ['id' => 17, 'eventDate' => (string) $timestamp])->data);
            $this->assertSame(['eventDate' => $expected], $mapper->toFormValues('tl_content', ['eventDate' => $timestamp]));
            $this->assertSame(['eventDate' => ''], $mapper->toFormValues('tl_content', ['eventDate' => null]));
        }
    }

    public function testDelegatesCompleteValuesToTheWidget(): void
    {
        $converter = new class() implements WidgetConverterInterface {
            public function supports(array $config): bool
            {
                return 'customRows' === ($config['inputType'] ?? null);
            }

            public function getSchema(array $config, array $schema): array
            {
                return ['type' => 'array', 'items' => ['type' => 'integer']];
            }

            public function convertToApiValue(mixed $value, array $config, array $schema): array
            {
                return array_map(intval(...), explode('|', (string) $value));
            }

            public function convertToFormValue(mixed $value, array $config, array $schema): array
            {
                return ['rows' => $value];
            }
        };

        $this->converters = new WidgetConverterRegistry([$converter]);

        $GLOBALS['TL_DCA']['tl_content']['fields']['rows'] = ['inputType' => 'customRows'];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($this->createContaoFrameworkStub([Controller::class => $controller]), $this->converters), $this->converters);

        $this->assertSame(['rows' => [1, 2]], $mapper->fromRow('tl_content', ['id' => 17, 'rows' => '1|2'])->data);
        $this->assertSame(['rows' => ['rows' => [3, 4]]], $mapper->toFormValues('tl_content', ['rows' => [3, 4]]));
    }

    public function testOmitsUnsupportedWidgetsAndRejectsTheirInput(): void
    {
        $widget = new class() extends Widget {
            public function __construct()
            {
            }
        };

        $GLOBALS['BE_FFL']['unsupported'] = $widget::class;
        $GLOBALS['TL_DCA']['tl_content']['fields']['payload'] = ['inputType' => 'unsupported', 'api' => ['schema' => ['type' => 'string']]];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($this->createContaoFrameworkStub([Controller::class => $controller]), $this->converters), $this->converters);

        $this->assertSame([], $mapper->fromRow('tl_content', ['id' => 17, 'payload' => 'private'])->data);
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Field "payload" is not writable');
        $mapper->toFormValues('tl_content', ['payload' => 'changed']);
    }

    public function testFormDefaultsIncludeOnlyWritableWidgetsWithoutReusingSecrets(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'alias' => ['inputType' => 'text', 'sql' => ['type' => 'string']],
            'password' => ['inputType' => 'password'],
            'locked' => ['inputType' => 'text', 'eval' => ['readonly' => true]],
            'unsupported' => ['inputType' => 'unknown'],
            'outside' => ['inputType' => 'text'],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($this->createContaoFrameworkStub([Controller::class => $controller]), $this->converters), $this->converters);
        $row = ['alias' => '', 'password' => 'stored-hash', 'locked' => 'fixed', 'outside' => 'hidden'];

        $this->assertSame(['alias' => '', 'password' => ''], $mapper->toFormDefaults('tl_content', $row, ['alias', 'password', 'locked', 'unsupported']));
    }

    public function testRejectsChangesToReadOnlyFields(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['locked'] = ['inputType' => 'text', 'api' => ['schema' => ['type' => 'string', 'readOnly' => true]]];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $framework = $this->createContaoFrameworkStub([Controller::class => $controller]);
        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters), $this->converters);

        $this->expectException(UnprocessableEntityHttpException::class);
        $mapper->toFormValues('tl_content', ['locked' => 'Changed']);
    }

    public function testRejectsPositionFieldsAbsentFromTheUpdateSchema(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['pid'] = ['sql' => ['type' => 'integer']];

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $framework = $this->createContaoFrameworkStub([Controller::class => $controller]);
        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters), $this->converters);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Field "pid" is not writable through the update operation.');
        $mapper->toFormValues('tl_content', ['pid' => 42]);
    }
}
