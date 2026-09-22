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
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\FileTree;
use Contao\PageTree;
use Contao\Password;
use Contao\RadioButton;
use Contao\SelectMenu;
use Contao\StringUtil;
use Contao\TextArea;
use Contao\TextField;
use Contao\Widget;
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
}
