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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\DataContainer\DataContainerRecordMapper;
use Contao\ApiBundle\DataContainer\DataContainerRelationDefinition;
use Contao\ApiBundle\DataContainer\DataContainerRelationReference;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\RelationAwareWidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CheckBox;
use Contao\Controller;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\Database;
use Contao\Date;
use Contao\FileTree;
use Contao\PageTree;
use Contao\Password;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;
use Contao\Widget;
use Doctrine\DBAL\DriverManager;
use Opis\JsonSchema\Validator as JsonSchemaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\LocaleSwitcher;

final class DataContainerRecordMapperTest extends ContaoTestCase
{
    private WidgetConverterRegistry $converters;

    private DataContainerRelationResolver $relationResolver;

    private LocaleSwitcher $localeSwitcher;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration();
        System::setContainer($container);

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
        $this->relationResolver = $this->createRelationResolver($this->converters);
        $this->localeSwitcher = $this->createLocaleSwitcher();

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
        $this->resetStaticProperties([Database::class, System::class]);

        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL']);

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

        $framework = $this->createContaoFrameworkMock([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher), $this->converters, $this->relationResolver);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'title' => 'Example', 'published' => '1', 'count' => '42', 'tags' => serialize(['one', 'two']), 'password' => 'hash', 'unknown' => 'private']);

        $this->assertSame(17, $record->id);
        $this->assertSame(['title' => 'Example', 'published' => true, 'count' => 42, 'tags' => ['one', 'two']], $record->data);
    }

    public function testReadsRecordMetadataWithoutAWidget(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_page';

        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => ['sql' => ['type' => 'integer']],
            'tstamp' => ['sql' => ['type' => 'integer']],
            'pid' => ['sql' => ['type' => 'integer']],
            'ptable' => ['sql' => ['type' => 'string']],
            'sorting' => ['sql' => ['type' => 'integer']],
            'internal' => ['sql' => ['type' => 'string']],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $mapper = new DataContainerRecordMapper(
            new DataContainerSchemaFactory(
                $this->createContaoFrameworkStub([
                    Controller::class => $controller,
                    System::class => $this->createAdapterStub(['loadLanguageFile']),
                ]),
                $this->converters,
                $this->relationResolver,
                $this->localeSwitcher,
            ),
            $this->converters,
            $this->relationResolver,
        );

        $record = $mapper->fromRow('tl_content', ['id' => 17, 'tstamp' => '123', 'pid' => '42', 'ptable' => 'tl_article', 'sorting' => '128', 'internal' => 'hidden']);

        $this->assertSame(17, $record->id);
        $this->assertEquals(
            [
                'tstamp' => date(\DateTimeInterface::ATOM, 123),
                'pid' => new DataContainerRelationReference(42, '/contao/api/dc/page/42'),
                'ptable' => 'tl_article',
                'sorting' => 128,
            ],
            $record->data,
        );

        $record = $mapper->fromRow('tl_content', ['id' => 18, 'tstamp' => '0', 'pid' => '42', 'ptable' => 'tl_article', 'sorting' => '128']);

        $this->assertNull($record->data['tstamp']);
    }

    public function testExposesFileReferencesAsUuids(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['singleSRC'] = ['inputType' => 'fileTree', 'sql' => ['type' => 'binary', 'length' => 16]];

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $factory = new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher);
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $mapper = new DataContainerRecordMapper($factory, $this->converters, $this->relationResolver);
        $record = $mapper->fromRow('tl_content', ['id' => 17, 'singleSRC' => hex2bin(str_replace('-', '', $uuid))]);

        $this->assertSame($uuid, $record->data['singleSRC']);
        $this->assertSame('uuid', $factory->create('tl_content')['properties']['singleSRC']['format']);
        $this->assertArrayNotHasKey('maxLength', $factory->create('tl_content')['properties']['singleSRC']);

        foreach (["\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0", '', null] as $uuid) {
            $record = $mapper->fromRow('tl_content', ['id' => 17, 'singleSRC' => $uuid]);
            $this->assertNull($record->data['singleSRC']);
        }
    }

    #[DataProvider('provideEmptyValues')]
    public function testEmptyValuesMatchTheWidgetSchema(array $config, mixed $stored, mixed $expected): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['value'] = $config;

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $factory = new DataContainerSchemaFactory($this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]), $this->converters, $this->relationResolver, $this->localeSwitcher);

        $mapper = new DataContainerRecordMapper($factory, $this->converters, $this->relationResolver);
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

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher), $this->converters, $this->relationResolver);

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
        $factory = new DataContainerSchemaFactory($this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]), $this->converters, $this->relationResolver, $this->localeSwitcher);

        $mapper = new DataContainerRecordMapper($factory, $this->converters, $this->relationResolver);
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

        $mapper = new DataContainerRecordMapper(
            new DataContainerSchemaFactory(
                $this->createContaoFrameworkStub([
                    Controller::class => $controller,
                    System::class => $this->createAdapterStub(['loadLanguageFile']),
                ]),
                $this->converters,
                $this->relationResolver,
                $this->localeSwitcher,
            ),
            $this->converters,
            $this->relationResolver,
        );

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
        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher), $this->converters, $this->relationResolver);

        $this->assertSame(['pages' => '1,2'], $mapper->toFormValues('tl_content', ['pages' => [1, 2]]));
    }

    public function testResolvesRelationsToAndFromIris(): void
    {
        $GLOBALS['TL_DCA']['tl_news']['fields']['jumpTo'] = [
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'relation' => ['type' => 'hasOne'],
        ];

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $factory = new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher);
        $mapper = new DataContainerRecordMapper($factory, $this->converters, $this->relationResolver);
        $schema = json_decode(json_encode($factory->create('tl_news'), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                'type' => ['object', 'null'],
                'required' => ['iri'],
                'properties' => [
                    'id' => ['type' => ['integer', 'string'], 'readOnly' => true],
                    'iri' => ['type' => 'string', 'format' => 'iri-reference'],
                ],
                'additionalProperties' => false,
            ],
            $factory->create('tl_news')['properties']['jumpTo'],
        );

        $this->assertTrue(new JsonSchemaValidator()->validate((object) ['jumpTo' => (object) ['iri' => '/contao/api/dc/page/42']], $schema)->isValid());
        $this->assertFalse(new JsonSchemaValidator()->validate((object) ['jumpTo' => 42], $schema)->isValid());
        $this->assertEquals(new DataContainerRelationReference(42, '/contao/api/dc/page/42'), $mapper->fromRow('tl_news', ['id' => 1, 'jumpTo' => 42])->data['jumpTo']);
        $this->assertSame(['jumpTo' => '42'], $mapper->toFormValues('tl_news', ['jumpTo' => ['id' => 42, 'iri' => '/contao/api/dc/page/42']]));
    }

    public function testResolvesTheParentReferenceOfANewRecord(): void
    {
        $GLOBALS['TL_DCA']['tl_article']['config']['ptable'] = 'tl_page';

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $this->createAdapterStub(['loadDataContainer']),
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher), $this->converters, $this->relationResolver);

        $this->assertSame(42, $mapper->toParentIdentifier('tl_article', ['iri' => '/contao/api/dc/page/42']));

        $this->expectException(UnprocessableEntityHttpException::class);

        $mapper->toParentIdentifier('tl_article', ['iri' => '']);
    }

    public function testResolvesRelationsProvidedByAWidgetConverter(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['destination'] = [
            'inputType' => 'customRelation',
            'eval' => ['targetTable' => 'tl_page'],
        ];

        $converters = new WidgetConverterRegistry([$this->createRelationAwareConverter()]);
        $resolver = $this->createRelationResolver($converters);
        $controller = $this->createAdapterStub(['loadDataContainer']);

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $factory = new DataContainerSchemaFactory($framework, $converters, $resolver, $this->localeSwitcher);
        $mapper = new DataContainerRecordMapper($factory, $converters, $resolver);

        $this->assertSame('iri-reference', $factory->create('tl_content')['properties']['destination']['properties']['iri']['format']);
        $this->assertEquals(new DataContainerRelationReference(42, '/contao/api/dc/page/42'), $mapper->fromRow('tl_content', ['id' => 1, 'destination' => 42])->data['destination']);
        $this->assertSame(['destination' => '42'], $mapper->toFormValues('tl_content', ['destination' => ['@id' => '/contao/api/dc/page/42', 'id' => 42]]));
    }

    public function testExposesDateFieldsAsDateTimesAndConvertsThemForTheWidget(): void
    {
        $container = new ContainerBuilder();
        $container->set('contao.routing.page_finder', $this->createStub(PageFinder::class));
        System::setContainer($container);

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $factory = new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher);
        $mapper = new DataContainerRecordMapper($factory, $this->converters, $this->relationResolver);
        $timestamp = mktime(14, 35, 0, 9, 17, 2026);

        foreach (['date' => ['d.m.Y', '17.09.2026'], 'time' => ['H:i', '14:35'], 'datim' => ['d.m.Y H:i', '17.09.2026 14:35']] as $rgxp => [$format, $expected]) {
            $GLOBALS['TL_CONFIG'][$rgxp.'Format'] = $format;
            $GLOBALS['TL_DCA']['tl_content']['fields']['eventDate'] = ['inputType' => 'text', 'sql' => ['type' => 'integer'], 'eval' => ['rgxp' => $rgxp]];
            $dateTime = date(\DateTimeInterface::ATOM, $timestamp);

            foreach (['read', 'create', 'update'] as $operation) {
                $this->assertSame(['type' => ['string', 'null'], 'format' => 'date-time'], $factory->createOperationSchemas('tl_content')[$operation]['properties']['eventDate']);
            }

            $this->assertSame(['eventDate' => $dateTime], $mapper->fromRow('tl_content', ['id' => 17, 'eventDate' => (string) $timestamp])->data);
            $this->assertSame(['eventDate' => null], $mapper->fromRow('tl_content', ['id' => 17, 'eventDate' => '0'])->data);
            $this->assertSame(['eventDate' => $expected], $mapper->toFormValues('tl_content', ['eventDate' => $dateTime]));
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

        $mapper = new DataContainerRecordMapper(
            new DataContainerSchemaFactory(
                $this->createContaoFrameworkStub([
                    Controller::class => $controller,
                    System::class => $this->createAdapterStub(['loadLanguageFile']),
                ]),
                $this->converters,
                $this->relationResolver,
                $this->localeSwitcher,
            ),
            $this->converters,
            $this->relationResolver,
        );

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

        $mapper = new DataContainerRecordMapper(
            new DataContainerSchemaFactory(
                $this->createContaoFrameworkStub([
                    Controller::class => $controller,
                    System::class => $this->createAdapterStub(['loadLanguageFile']),
                ]),
                $this->converters,
                $this->relationResolver,
                $this->localeSwitcher,
            ),
            $this->converters,
            $this->relationResolver,
        );

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

        $mapper = new DataContainerRecordMapper(
            new DataContainerSchemaFactory(
                $this->createContaoFrameworkStub([
                    Controller::class => $controller,
                    System::class => $this->createAdapterStub(['loadLanguageFile']),
                ]),
                $this->converters,
                $this->relationResolver,
                $this->localeSwitcher,
            ),
            $this->converters,
            $this->relationResolver,
        );

        $row = ['alias' => '', 'password' => 'stored-hash', 'locked' => 'fixed', 'outside' => 'hidden'];

        $this->assertSame(['alias' => '', 'password' => ''], $mapper->toFormDefaults('tl_content', $row, ['alias', 'password', 'locked', 'unsupported']));
    }

    public function testRejectsChangesToReadOnlyFields(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['locked'] = ['inputType' => 'text', 'api' => ['schema' => ['type' => 'string', 'readOnly' => true]]];

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher), $this->converters, $this->relationResolver);

        $this->expectException(UnprocessableEntityHttpException::class);
        $mapper->toFormValues('tl_content', ['locked' => 'Changed']);
    }

    public function testRejectsPositionFieldsAbsentFromTheUpdateSchema(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['pid'] = ['sql' => ['type' => 'integer']];

        $controller = $this->createAdapterStub(['loadDataContainer']);

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $this->createAdapterStub(['loadLanguageFile']),
        ]);

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters, $this->relationResolver, $this->localeSwitcher), $this->converters, $this->relationResolver);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Field "pid" is not writable through the update operation.');
        $mapper->toFormValues('tl_content', ['pid' => 42]);
    }

    private function createRelationResolver(WidgetConverterRegistry $converters): DataContainerRelationResolver
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE tl_page (id INTEGER PRIMARY KEY, tstamp INTEGER NOT NULL, title VARCHAR(255))');
        $connection->insert('tl_page', ['id' => 42, 'tstamp' => 1, 'title' => 'Example']);

        $container = System::getContainer();

        if ($container instanceof ContainerBuilder) {
            $container->set('database_connection', $connection);
        }

        $resource = new ApiResource(
            operations: [new Get(name: 'page_get', extraProperties: ['contao' => ['resource' => 'page', 'parents' => []]])],
            extraProperties: ['contao' => ['table' => 'tl_page']],
        );

        $metadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadataFactory
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, [$resource]))
        ;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('/contao/api/dc/page/42')
        ;

        $router
            ->method('match')
            ->willReturn(['_route' => 'page_get', 'id' => '42'])
        ;

        return new DataContainerRelationResolver($connection, new ForeignKeyParser($connection), $converters, $metadataFactory, $router, $this->createStub(DcaHierarchy::class));
    }

    private function createRelationAwareConverter(): RelationAwareWidgetConverterInterface
    {
        return new class() implements RelationAwareWidgetConverterInterface {
            public function supports(array $config): bool
            {
                return 'customRelation' === ($config['inputType'] ?? null);
            }

            public function getSchema(array $config, array $schema): array
            {
                return ['type' => 'integer'];
            }

            public function convertToApiValue(mixed $value, array $config, array $schema): int
            {
                return (int) $value;
            }

            public function convertToFormValue(mixed $value, array $config, array $schema): string
            {
                return (string) $value;
            }

            public function getRelation(array $config): DataContainerRelationDefinition|null
            {
                $table = $config['eval']['targetTable'] ?? null;

                return \is_string($table) ? new DataContainerRelationDefinition($table) : null;
            }
        };
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
