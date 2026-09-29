<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Schema;

use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CheckBox;
use Contao\Controller;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\FileTree;
use Contao\PageTree;
use Contao\Password;
use Contao\SelectMenu;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Contao\TextArea;
use Contao\TextField;
use Contao\Validator;
use Contao\Widget;
use Symfony\Component\Translation\LocaleSwitcher;

final class DataContainerSchemaFactoryTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration();
        System::setContainer($container);

        $GLOBALS['BE_FFL'] = [
            'text' => TextField::class,
            'textarea' => TextArea::class,
            'select' => SelectMenu::class,
            'custom' => TextField::class,
            'checkbox' => CheckBox::class,
            'fileTree' => FileTree::class,
            'pageTree' => PageTree::class,
            'password' => Password::class,
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL'], $GLOBALS['TL_LANG']);

        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testCreatesSchemaFromDcaFields(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => [
                'sql' => [
                    'type' => 'integer',
                ],
            ],
            'title' => [
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => true,
                    'maxlength' => 64,
                ],
                'sql' => [
                    'type' => 'string',
                    'length' => 64,
                ],
            ],
            'published' => [
                'inputType' => 'checkbox',
                'sql' => [
                    'type' => 'boolean',
                ],
            ],
            'type' => [
                'inputType' => 'select',
                'options' => ['a', 'b'],
                'sql' => [
                    'type' => 'string',
                    'length' => 1,
                ],
            ],
            'email' => [
                'inputType' => 'text',
                'eval' => [
                    'rgxp' => 'email',
                ],
                'sql' => [
                    'type' => 'string',
                ],
            ],
            'digits' => [
                'inputType' => 'text',
                'eval' => [
                    'rgxp' => 'digit',
                ],
                'sql' => [
                    'type' => 'string',
                ],
            ],
            'text' => [
                'inputType' => 'textarea',
                'eval' => [
                    'rte' => 'tinyMCE',
                ],
                'sql' => [
                    'type' => 'string',
                ],
            ],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());

        $schema = $factory->create('tl_content');

        $this->assertSame('object', $schema['type']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertArrayNotHasKey('required', $schema);
        $this->assertSame(['type' => 'integer', 'readOnly' => true], $schema['properties']['id']);
        $this->assertSame(['type' => 'string', 'maxLength' => 64], $schema['properties']['title']);
        $this->assertSame(['type' => 'boolean'], $schema['properties']['published']);
        $this->assertSame(['type' => 'string', 'maxLength' => 1, 'enum' => ['a', 'b']], $schema['properties']['type']);
        $this->assertSame(['type' => 'string', 'format' => 'email'], $schema['properties']['email']);
        $this->assertSame(['type' => 'string', 'pattern' => Validator::REGEXP_DIGIT], $schema['properties']['digits']);
        $this->assertSame(['type' => 'string', 'contentMediaType' => 'text/html'], $schema['properties']['text']);
    }

    public function testRequiresExplicitWidgetSupportEvenWithSchemaOverrides(): void
    {
        $widget = new class() extends Widget {
            public function __construct()
            {
            }
        };

        $GLOBALS['BE_FFL']['unsupported'] = $widget::class;

        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => ['sql' => ['type' => 'integer']],
            'title' => ['inputType' => 'text', 'sql' => ['type' => 'string']],
            'unsupported' => ['inputType' => 'unsupported', 'eval' => ['mandatory' => true], 'api' => ['schema' => ['type' => 'string']]],
            'missing' => ['inputType' => 'unregistered', 'sql' => ['type' => 'string']],
            'internal' => ['sql' => ['type' => 'string']],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());

        $this->assertSame(['id', 'title'], array_keys($factory->create('tl_content')['properties']));
        $this->assertArrayNotHasKey('required', $factory->create('tl_content'));

        foreach (['create', 'update'] as $operation) {
            $this->assertSame(['title'], array_keys($factory->createOperationSchemas('tl_content')[$operation]['properties']));
        }
    }

    public function testSupportsCustomFieldSchemas(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'secret' => ['inputType' => 'custom', 'api' => ['schema' => ['type' => 'string', 'writeOnly' => true]]],
            'locked' => ['inputType' => 'custom', 'api' => ['schema' => ['type' => 'string', 'readOnly' => true]]],
            'disabled' => ['inputType' => 'text', 'eval' => ['disabled' => true], 'sql' => ['type' => 'string']],
            'passwords' => ['inputType' => 'password', 'eval' => ['multiple' => true], 'sql' => ['type' => 'string']],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $properties = $factory->create('tl_content')['properties'];

        $this->assertTrue($properties['secret']['writeOnly']);
        $this->assertTrue($properties['locked']['readOnly']);
        $this->assertTrue($properties['disabled']['readOnly']);
        $this->assertTrue($properties['passwords']['writeOnly']);
        $this->assertArrayNotHasKey('writeOnly', $properties['passwords']['items']);
    }

    public function testCreatesEnglishTitlesAndDescriptionsFromLabelsUnlessExplicitlyConfigured(): void
    {
        $GLOBALS['TL_LANG']['MSC']['apiTestImgSize'] = ['Deutsche Bildgröße', 'Deutsche Hilfe.'];

        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'title' => ['inputType' => 'text', 'label' => ['Deutscher Titel', 'Deutsche Hilfe.']],
            'size' => ['inputType' => 'text', 'label' => &$GLOBALS['TL_LANG']['MSC']['apiTestImgSize']],
            'labelOnly' => ['inputType' => 'text', 'label' => 'English label'],
            'explicit' => [
                'inputType' => 'text',
                'label' => ['Deutsche Bezeichnung', 'Deutsche Hilfe.'],
                'api' => ['schema' => ['title' => 'Explicit title', 'description' => 'Explicit description.']],
            ],
        ];

        $localeSwitcher = $this->createLocaleSwitcher('de');
        $controller = $this->createAdapterStub(['loadDataContainer']);
        $loadedLanguageFiles = [];

        $system = $this->createAdapterMock(['loadLanguageFile']);
        $system
            ->expects($this->exactly(4))
            ->method('loadLanguageFile')
            ->willReturnCallback(
                static function (string $name) use ($localeSwitcher, &$loadedLanguageFiles): void {
                    $loadedLanguageFiles[] = [$localeSwitcher->getLocale(), $name];

                    if ('default' === $name) {
                        $GLOBALS['TL_LANG']['MSC']['apiTestImgSize'] = 'en' === $localeSwitcher->getLocale()
                            ? ['Image size', 'Here you can set the image dimensions.']
                            : ['Deutsche Bildgröße', 'Deutsche Hilfe.'];
                    } elseif ('en' === $localeSwitcher->getLocale()) {
                        $GLOBALS['TL_DCA']['tl_content']['fields']['title']['label'] = ['English title', 'English <em>help</em>.'];
                    } else {
                        $GLOBALS['TL_DCA']['tl_content']['fields']['title']['label'] = ['Deutscher Titel', 'Deutsche Hilfe.'];
                    }
                },
            )
        ;

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controller,
            System::class => $system,
        ]);

        $factory = new DataContainerSchemaFactory($framework, new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $localeSwitcher);
        $properties = $factory->create('tl_content')['properties'];

        $this->assertSame('English title', $properties['title']['title']);
        $this->assertSame('English help.', $properties['title']['description']);
        $this->assertSame('Image size', $properties['size']['title']);
        $this->assertSame('Here you can set the image dimensions.', $properties['size']['description']);
        $this->assertSame('English label', $properties['labelOnly']['title']);
        $this->assertArrayNotHasKey('description', $properties['labelOnly']);
        $this->assertSame('Explicit title', $properties['explicit']['title']);
        $this->assertSame('Explicit description.', $properties['explicit']['description']);
        $this->assertSame([['en', 'default'], ['en', 'tl_content'], ['de', 'default'], ['de', 'tl_content']], $loadedLanguageFiles);
        $this->assertSame('de', $localeSwitcher->getLocale());
        $this->assertSame(['Deutscher Titel', 'Deutsche Hilfe.'], $GLOBALS['TL_DCA']['tl_content']['fields']['title']['label']);
        $this->assertSame(['Deutsche Bildgröße', 'Deutsche Hilfe.'], $GLOBALS['TL_DCA']['tl_content']['fields']['size']['label']);
    }

    public function testInheritsWidgetSchemaForEveryFieldUsingThatWidget(): void
    {
        $widget = new class() extends FileTree {
            public function __construct()
            {
            }
        };

        $GLOBALS['BE_FFL']['customFiles'] = $widget::class;

        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'single' => ['inputType' => 'customFiles', 'sql' => ['type' => 'binary', 'length' => 16]],
            'multiple' => ['inputType' => 'customFiles', 'eval' => ['multiple' => true], 'sql' => ['type' => 'blob']],
            'textual' => ['inputType' => 'customFiles', 'eval' => ['binary' => false]],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $properties = $factory->create('tl_content')['properties'];

        $this->assertSame(['type' => ['string', 'null'], 'format' => 'uuid'], $properties['single']);
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'string', 'format' => 'uuid']], $properties['multiple']);
        $this->assertSame(['type' => ['string', 'null'], 'format' => 'uuid'], $properties['textual']);
    }

    public function testConvertersCanSupportUnmodifiedThirdPartyWidgets(): void
    {
        $widget = new class() extends Widget {
            public function __construct()
            {
            }
        };

        $GLOBALS['BE_FFL']['customText'] = $widget::class;

        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'first' => ['inputType' => 'customText'],
            'second' => ['inputType' => 'customText'],
            'secret' => ['inputType' => 'customText', 'api' => ['schema' => ['writeOnly' => true]]],
        ];

        $converter = $this->createMock(WidgetConverterInterface::class);
        $converter
            ->method('supports')
            ->willReturnCallback(static fn (array $config): bool => 'customText' === ($config['inputType'] ?? null))
        ;

        $converter
            ->expects($this->exactly(3))
            ->method('getSchema')
            ->willReturn(['type' => 'string', 'readOnly' => true])
        ;

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([$converter, new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $properties = $factory->create('tl_content')['properties'];

        $this->assertSame($properties['first'], $properties['second']);
        $this->assertSame(['type' => 'string', 'readOnly' => true], $properties['first']);
        $this->assertTrue($properties['secret']['writeOnly']);
    }

    public function testPageTreeAndCheckboxUseTheirConfiguredMultiplicity(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'page' => ['inputType' => 'pageTree'],
            'pages' => ['inputType' => 'pageTree', 'eval' => ['multiple' => true]],
            'enabled' => ['inputType' => 'checkbox', 'sql' => ['type' => 'string', 'length' => 1]],
            'options' => ['inputType' => 'checkbox', 'options' => ['one', 'two'], 'eval' => ['multiple' => true]],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $properties = $factory->create('tl_content')['properties'];

        $this->assertSame('integer', $properties['page']['type']);
        $this->assertSame('integer', $properties['pages']['items']['type']);
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'integer']], $properties['pages']);
        $this->assertSame('boolean', $properties['enabled']['type']);
        $this->assertArrayNotHasKey('maxLength', $properties['enabled']);
        $this->assertSame('string', $properties['options']['items']['type']);
        $this->assertSame(['one', 'two'], $properties['options']['items']['enum']);
    }

    public function testDistinguishesMoveManagedFieldsFromReadOnlyFields(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => ['sql' => ['type' => 'integer']],
            'tstamp' => ['sql' => ['type' => 'integer']],
            'pid' => ['sql' => ['type' => 'integer']],
            'ptable' => ['sql' => ['type' => 'string']],
            'sorting' => ['sql' => ['type' => 'integer']],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $properties = $factory->create('tl_content')['properties'];

        foreach (['pid', 'ptable', 'sorting'] as $field) {
            $this->assertStringContainsString('move operation', $properties[$field]['description']);
            $this->assertArrayNotHasKey('readOnly', $properties[$field]);
        }

        foreach (['id', 'tstamp'] as $field) {
            $this->assertTrue($properties[$field]['readOnly']);
        }

        $GLOBALS['TL_DCA']['tl_content']['fields']['pid']['eval']['readonly'] = true;
        $this->assertTrue($factory->create('tl_content')['properties']['pid']['readOnly']);
    }

    public function testCreatesDateTimeSchemasForDateFieldsAndTheTimestampMetadata(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'tstamp' => ['sql' => ['type' => 'integer', 'default' => 0]],
            'eventDate' => ['inputType' => 'text', 'eval' => ['rgxp' => 'date'], 'sql' => ['type' => 'integer', 'default' => 0]],
            'eventTime' => ['inputType' => 'text', 'eval' => ['rgxp' => 'time'], 'sql' => ['type' => 'string', 'default' => '456']],
            'eventDateTime' => ['inputType' => 'text', 'eval' => ['rgxp' => 'datim'], 'sql' => ['type' => 'integer']],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $properties = $factory->create('tl_content')['properties'];

        $this->assertSame(['type' => ['string', 'null'], 'format' => 'date-time', 'default' => null, 'readOnly' => true], $properties['tstamp']);
        $this->assertSame(['type' => ['string', 'null'], 'format' => 'date-time', 'default' => null], $properties['eventDate']);
        $this->assertSame(['type' => ['string', 'null'], 'format' => 'date-time', 'default' => date(\DateTimeInterface::ATOM, 456)], $properties['eventTime']);
        $this->assertSame(['type' => ['string', 'null'], 'format' => 'date-time'], $properties['eventDateTime']);

        $schema = json_decode(json_encode($factory->create('tl_content'), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $validator = new \Opis\JsonSchema\Validator();

        $this->assertTrue($validator->validate((object) ['eventDate' => date(\DateTimeInterface::ATOM, 123)], $schema)->isValid());
        $this->assertTrue($validator->validate((object) ['eventDate' => null], $schema)->isValid());
        $this->assertFalse($validator->validate((object) ['eventDate' => 123], $schema)->isValid());
        $this->assertFalse($validator->validate((object) ['eventDate' => 'not a date'], $schema)->isValid());
    }

    public function testCreatesDifferentRequestAndResponseSchemas(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => ['sql' => ['type' => 'integer']],
            'pid' => ['sql' => ['type' => 'integer']],
            'ptable' => ['sql' => ['type' => 'string']],
            'sorting' => ['sql' => ['type' => 'integer']],
            'title' => ['inputType' => 'text', 'sql' => ['type' => 'string'], 'eval' => ['mandatory' => true]],
            'secret' => ['inputType' => 'password', 'sql' => ['type' => 'string'], 'eval' => ['mandatory' => true]],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $schemas = $factory->createOperationSchemas('tl_content');

        $this->assertSame(['id', 'pid', 'ptable', 'sorting', 'title'], array_keys($schemas['read']['properties']));
        $this->assertSame(['title', 'secret'], array_keys($schemas['create']['properties']));
        $this->assertSame(['title', 'secret'], array_keys($schemas['update']['properties']));
        $this->assertArrayNotHasKey('required', $schemas['create']);
        $this->assertArrayNotHasKey('required', $schemas['read']);
        $this->assertArrayNotHasKey('required', $schemas['update']);
        $this->assertFalse($schemas['update']['additionalProperties']);
    }

    public function testAnEmptyUpdateSchemaAcceptsOnlyAnEmptyObject(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['id'] = ['sql' => ['type' => 'integer']];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class), new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]), $this->createLocaleSwitcher());
        $schema = json_decode(json_encode($factory->createOperationSchemas('tl_content')['update'], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $validator = new \Opis\JsonSchema\Validator();

        $this->assertTrue($validator->validate(new \stdClass(), $schema)->isValid());
        $this->assertFalse($validator->validate((object) ['id' => 1], $schema)->isValid());
    }

    private function createLocaleSwitcher(string $locale = 'en'): LocaleSwitcher
    {
        $localeSwitcher = $this->createStub(LocaleSwitcher::class);
        $localeSwitcher
            ->method('getLocale')
            ->willReturnCallback(
                static function () use (&$locale): string {
                    return $locale;
                },
            )
        ;

        $localeSwitcher
            ->method('runWithLocale')
            ->willReturnCallback(
                static function (string $newLocale, callable $callback) use (&$locale): mixed {
                    $previousLocale = $locale;
                    $locale = $newLocale;

                    try {
                        return $callback($newLocale);
                    } finally {
                        $locale = $previousLocale;
                    }
                },
            )
        ;

        return $localeSwitcher;
    }
}
