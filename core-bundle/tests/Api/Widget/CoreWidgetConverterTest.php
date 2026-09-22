<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Api\Widget;

use Contao\CheckBox;
use Contao\CheckBoxWizard;
use Contao\ChmodTable;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\CudTable;
use Contao\FileTree;
use Contao\ImageSize;
use Contao\InputUnit;
use Contao\KeyValueWizard;
use Contao\ListWizard;
use Contao\MetaWizard;
use Contao\ModuleWizard;
use Contao\OptionWizard;
use Contao\PageTree;
use Contao\Password;
use Contao\Picker;
use Contao\RadioButton;
use Contao\RadioTable;
use Contao\RootPageDependentSelect;
use Contao\RowWizard;
use Contao\SectionWizard;
use Contao\SelectMenu;
use Contao\SerpPreview;
use Contao\StringUtil;
use Contao\TableWizard;
use Contao\TextArea;
use Contao\TextField;
use Contao\TimePeriod;
use Contao\Upload;
use Contao\Widget;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CoreWidgetConverterTest extends TestCase
{
    private array|null $widgets;

    protected function setUp(): void
    {
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL'] = [
            'text' => TextField::class,
            'textarea' => TextArea::class,
            'select' => SelectMenu::class,
            'radio' => RadioButton::class,
            'checkbox' => CheckBox::class,
            'password' => Password::class,
            'fileTree' => FileTree::class,
            'pageTree' => PageTree::class,
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }
    }

    public function testSupportsCoreWidgetsAndTheirSubclasses(): void
    {
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));

        foreach (array_keys($GLOBALS['BE_FFL']) as $inputType) {
            $this->assertTrue($converter->supports(['inputType' => $inputType]));
        }

        $widget = new class() extends TextField {
            public function __construct()
            {
            }
        };
        $GLOBALS['BE_FFL']['custom'] = $widget::class;
        $GLOBALS['BE_FFL']['unsupported'] = Widget::class;

        $this->assertTrue($converter->supports(['inputType' => 'custom']));
        $this->assertFalse($converter->supports(['inputType' => 'unsupported']));
        $this->assertFalse($converter->supports(['inputType' => 'unknown']));
        $this->assertFalse($converter->supports([]));
    }

    public function testConvertsStoredSelectionsToTypedApiArrays(): void
    {
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));
        $schema = ['type' => 'array', 'items' => ['type' => 'integer']];
        $config = ['inputType' => 'select'];

        $this->assertSame([12, 34], $converter->convertToApiValue(serialize(['12', '34']), $config, $schema));
        $this->assertSame([12, 34], $converter->convertToApiValue('12,34', $config + ['eval' => ['csv' => ',']], $schema));
        $this->assertSame([12, 34], $converter->convertToApiValue('[12,34]', $config + ['sql' => ['type' => 'json']], $schema));
        $this->assertSame([], $converter->convertToApiValue(null, $config, $schema));
        $this->assertSame([], $converter->convertToApiValue('', $config, $schema));
        $this->assertFalse($converter->convertToApiValue('', ['inputType' => 'checkbox'], ['type' => 'boolean']));
        $this->assertSame(1.5, $converter->convertToApiValue('1.5', $config, ['type' => 'number']));
    }

    public function testConvertsBinaryFileReferencesAndFormSelections(): void
    {
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));
        $uuid = '12345678-1234-1234-1234-123456789abc';
        $binary = StringUtil::uuidToBin($uuid);
        $config = ['inputType' => 'fileTree'];
        $schema = $converter->getSchema($config, ['type' => 'string', 'maxLength' => 16]);

        $this->assertSame(['type' => ['string', 'null'], 'format' => 'uuid'], $schema);
        $this->assertSame($uuid, $converter->convertToApiValue($binary, $config, $schema));
        $this->assertNull($converter->convertToApiValue('', $config, $schema));
        $this->assertNull($converter->convertToApiValue(null, $config, $schema));
        $this->assertSame($binary, $converter->convertToApiValue($binary, $config + ['eval' => ['binary' => false]], $schema));
        $this->assertSame($uuid, $converter->convertToApiValue($uuid, $config + ['eval' => ['binary' => false]], $schema));
        $arraySchema = $converter->getSchema($config + ['eval' => ['multiple' => true]], []);
        $this->assertSame([$uuid, $uuid], $converter->convertToApiValue(serialize([$binary, $binary]), $config, $arraySchema));
        $this->assertSame($uuid.','.$uuid, $converter->convertToFormValue([$uuid, $uuid], $config, $arraySchema));
        $this->assertSame($uuid, $converter->convertToFormValue($uuid, $config, $schema));
    }

    public function testPreparesFormInputInsteadOfSerializedStorage(): void
    {
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));
        $schema = ['type' => 'array', 'items' => ['type' => 'integer']];

        $this->assertSame(['12', '34'], $converter->convertToFormValue([12, 34], ['inputType' => 'select'], $schema));
        $this->assertSame('12,34', $converter->convertToFormValue([12, 34], ['inputType' => 'pageTree'], $schema));
        $this->assertSame('', $converter->convertToFormValue([], ['inputType' => 'pageTree'], $schema));
        $this->assertSame('', $converter->convertToFormValue(false, ['inputType' => 'checkbox'], ['type' => 'boolean']));
        $this->assertSame('1', $converter->convertToFormValue(true, ['inputType' => 'checkbox'], ['type' => 'boolean']));
        $this->assertSame('', $converter->convertToFormValue(null, ['inputType' => 'text'], ['type' => 'string']));
    }

    #[DataProvider('provideStructuredWidgets')]
    public function testConvertsStructuredWidgets(string $widget, array $stored, mixed $expected): void
    {
        $GLOBALS['BE_FFL']['structured'] = $widget;
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));
        $config = ['inputType' => 'structured', 'sql' => ['type' => 'blob']];
        $schema = $converter->getSchema($config, ['type' => 'string', 'maxLength' => 16, 'enum' => ['irrelevant']]);
        $api = $converter->convertToApiValue(serialize($stored), $config, $schema);

        $this->assertTrue($converter->supports($config));
        $this->assertSame(json_encode($expected, JSON_THROW_ON_ERROR), json_encode($api, JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('enum', $schema);
        $this->assertArrayNotHasKey('maxLength', $schema);
        $validator = new Validator();
        $this->assertTrue($validator->validate($api, json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR))->isValid());
        $form = $converter->convertToFormValue($api, $config, $schema);
        $this->assertSame(json_encode($api, JSON_THROW_ON_ERROR), json_encode($converter->convertToApiValue(serialize($form), $config, $schema), JSON_THROW_ON_ERROR));
    }

    public static function provideStructuredWidgets(): iterable
    {
        yield 'list' => [ListWizard::class, [2 => 'first', 4 => 'second'], ['first', 'second']];
        yield 'table' => [TableWizard::class, [['a', 'b'], ['c', 'd']], [['a', 'b'], ['c', 'd']]];
        yield 'unit' => [InputUnit::class, ['value' => '12.5', 'unit' => 'px'], (object) ['value' => '12.5', 'unit' => 'px']];
        yield 'period' => [TimePeriod::class, ['value' => 12, 'unit' => 'days'], (object) ['value' => '12', 'unit' => 'days']];
        yield 'key value' => [KeyValueWizard::class, [['key' => 'foo', 'value' => 'bar']], [(object) ['key' => 'foo', 'value' => 'bar']]];
        yield 'options' => [OptionWizard::class, [['value' => 'a', 'label' => 'A', 'default' => '1', 'group' => '']], [(object) ['value' => 'a', 'label' => 'A', 'default' => true, 'group' => false]]];
        yield 'modules' => [ModuleWizard::class, [['mod' => 'content-42', 'col' => 'main', 'enable' => '1']], [(object) ['mod' => 'content-42', 'col' => 'main', 'enable' => true]]];
        yield 'sections' => [SectionWizard::class, [['title' => 'Test', 'id' => 'test', 'template' => '', 'position' => 'before']], [(object) ['title' => 'Test', 'id' => 'test', 'template' => '', 'position' => 'before']]];
        yield 'meta' => [MetaWizard::class, ['en' => ['title' => 'Test', 'custom' => 'Value']], (object) ['en' => (object) ['title' => 'Test', 'custom' => 'Value']]];
        yield 'empty meta' => [MetaWizard::class, [], new \stdClass()];
        yield 'root pages' => [RootPageDependentSelect::class, [1 => 12, 2 => 34], (object) ['1' => '12', '2' => '34']];
        yield 'image size' => [ImageSize::class, [100, 200, 'crop'], ['100', '200', 'crop']];
        yield 'empty image size' => [ImageSize::class, [], ['', '', '']];
        yield 'chmod' => [ChmodTable::class, ['u1', 'g2'], ['u1', 'g2']];
        yield 'cud' => [CudTable::class, ['tl_content::create'], ['tl_content::create']];
    }

    public function testPreparesStructuredFormValues(): void
    {
        $GLOBALS['BE_FFL']['options'] = OptionWizard::class;
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));
        $config = ['inputType' => 'options'];
        $schema = $converter->getSchema($config, []);

        $this->assertSame(
            [['value' => 'a', 'label' => 'A', 'default' => '1', 'group' => '']],
            $converter->convertToFormValue([(object) ['value' => 'a', 'label' => 'A', 'default' => true]], $config, $schema),
        );
        $this->assertSame([], $converter->convertToFormValue([], $config, $schema));
    }

    public function testSupportsAdditionalSelections(): void
    {
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));
        $GLOBALS['BE_FFL']['wizard'] = CheckBoxWizard::class;
        $GLOBALS['BE_FFL']['radioTable'] = RadioTable::class;
        $GLOBALS['BE_FFL']['picker'] = Picker::class;
        $schema = $converter->getSchema(['inputType' => 'wizard'], ['type' => 'string', 'enum' => ['a', 'b']]);
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b']]], $schema);
        $this->assertSame(['a', 'b'], $converter->convertToApiValue(serialize(['a', 'b']), ['inputType' => 'wizard'], $schema));
        $this->assertTrue($converter->supports(['inputType' => 'radioTable']));
        $schema = $converter->getSchema(['inputType' => 'picker', 'eval' => ['multiple' => true]], []);
        $this->assertSame([12, 34], $converter->convertToApiValue(serialize(['12', '34']), ['inputType' => 'picker'], $schema));
        $this->assertSame('12,34', $converter->convertToFormValue([12, 34], ['inputType' => 'picker'], $schema));
    }

    public function testExcludesWidgetsWithoutAValueConversion(): void
    {
        $converter = new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)));

        foreach ([Upload::class, SerpPreview::class, RowWizard::class] as $widget) {
            $GLOBALS['BE_FFL']['unsupported'] = $widget;
            $this->assertFalse($converter->supports(['inputType' => 'unsupported']));
        }
    }
}
