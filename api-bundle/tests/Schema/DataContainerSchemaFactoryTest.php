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
use Contao\CheckBox;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\ApiWidgetInterface;
use Contao\FileTree;
use Contao\PageTree;
use Contao\Password;
use Contao\SelectMenu;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;
use Contao\Validator;
use Contao\Widget;

final class DataContainerSchemaFactoryTest extends ContaoTestCase
{
    private array|null $widgets = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL'] = [
            'text' => TextField::class,
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
        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }

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
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));

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
    }

    public function testRequiresExplicitWidgetSupportEvenWithSchemaOverrides(): void
    {
        $widget = new class() extends Widget {
            public function __construct()
            {
            }

            public static function getApiSchema(array $config, array $schema): array
            {
                return ['type' => 'string'];
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
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));

        $this->assertFalse(is_a(Widget::class, ApiWidgetInterface::class, true));
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
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));
        $properties = $factory->create('tl_content')['properties'];

        $this->assertTrue($properties['secret']['writeOnly']);
        $this->assertTrue($properties['locked']['readOnly']);
        $this->assertTrue($properties['disabled']['readOnly']);
        $this->assertTrue($properties['passwords']['writeOnly']);
        $this->assertArrayNotHasKey('writeOnly', $properties['passwords']['items']);
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
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));
        $properties = $factory->create('tl_content')['properties'];

        $this->assertSame(['type' => ['string', 'null'], 'format' => 'uuid'], $properties['single']);
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'string', 'format' => 'uuid']], $properties['multiple']);
        $this->assertSame(['type' => ['string', 'null'], 'format' => 'uuid'], $properties['textual']);
    }

    public function testCustomWidgetsCanDescribeTheirValuesWithoutFieldOverrides(): void
    {
        $widget = new class() extends Widget implements ApiWidgetInterface {
            public function __construct()
            {
            }

            public static function getApiSchema(array $config, array $schema): array
            {
                return array_replace($schema, ['type' => 'string', 'readOnly' => true]);
            }
        };
        $GLOBALS['BE_FFL']['customText'] = $widget::class;
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'first' => ['inputType' => 'customText'],
            'second' => ['inputType' => 'customText'],
            'secret' => ['inputType' => 'customText', 'api' => ['schema' => ['writeOnly' => true]]],
        ];
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));
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
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));
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
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));
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
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));
        $schemas = $factory->createOperationSchemas('tl_content');

        $this->assertSame(['id', 'pid', 'ptable', 'sorting', 'title'], array_keys($schemas['read']['properties']));
        $this->assertSame(['pid', 'ptable', 'title', 'secret'], array_keys($schemas['create']['properties']));
        $this->assertSame(['title', 'secret'], array_keys($schemas['update']['properties']));
        $this->assertArrayNotHasKey('required', $schemas['create']);
        $this->assertArrayNotHasKey('required', $schemas['read']);
        $this->assertArrayNotHasKey('required', $schemas['update']);
        $this->assertFalse($schemas['update']['additionalProperties']);
    }

    public function testAnEmptyUpdateSchemaAcceptsOnlyAnEmptyObject(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['id'] = ['sql' => ['type' => 'integer']];
        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));
        $schema = json_decode(json_encode($factory->createOperationSchemas('tl_content')['update'], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $validator = new \Opis\JsonSchema\Validator();

        $this->assertTrue($validator->validate(new \stdClass(), $schema)->isValid());
        $this->assertFalse($validator->validate((object) ['id' => 1], $schema)->isValid());
    }
}
