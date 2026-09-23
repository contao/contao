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

use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Api\Widget\RowWizardConverter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\FileTree;
use Contao\Password;
use Contao\RowWizard;
use Contao\StringUtil;
use Contao\TextField;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class RowWizardConverterTest extends TestCase
{
    private array|null $widgets;

    protected function setUp(): void
    {
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL'] = ['rows' => RowWizard::class, 'text' => TextField::class, 'file' => FileTree::class, 'password' => Password::class];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }
    }

    public function testConvertsCellsAndPreparesRowMarkers(): void
    {
        $config = ['inputType' => 'rows', 'eval' => ['actions' => ['enable']], 'fields' => [
            'id' => ['inputType' => 'text', 'eval' => ['maxlength' => 20]],
            'file' => ['inputType' => 'file'],
        ]];

        $converter = $this->createConverter();
        $schema = $converter->getSchema($config, ['type' => 'string']);

        $this->assertTrue($converter->supports($config));
        $this->assertSame(['type' => 'string', 'maxLength' => 20], $schema['items']['properties']['id']);

        $uuid = '12345678-1234-1234-1234-123456789abc';
        $rows = $converter->convertToApiValue(serialize([4 => ['id' => 'test', 'file' => StringUtil::uuidToBin($uuid), 'enable' => '1']]), $config, $schema);

        $this->assertSame('[{"id":"test","file":"'.$uuid.'","enable":true}]', json_encode($rows, JSON_THROW_ON_ERROR));

        $this->assertSame(
            [
                '_rows' => ['1'],
                ['id' => 'test', 'file' => $uuid, 'enable' => '1'],
            ],
            $converter->convertToFormValue($rows, $config, $schema),
        );

        $this->assertSame(['_rows' => []], $converter->convertToFormValue([], $config, $schema));
        $this->assertSame([], $converter->convertToApiValue('', $config, $schema));
    }

    public function testDelegatesToThirdPartyConverters(): void
    {
        $field = ['inputType' => 'custom'];

        $child = $this->createMock(WidgetConverterInterface::class);
        $child
            ->method('supports')
            ->willReturnCallback(static fn (array $config): bool => 'custom' === ($config['inputType'] ?? null))
        ;

        $child
            ->expects($this->once())
            ->method('getSchema')
            ->with($field, ['type' => 'string'])
            ->willReturn(['type' => 'integer'])
        ;

        $child
            ->expects($this->once())
            ->method('convertToApiValue')
            ->with('stored', $field, ['type' => 'integer'])
            ->willReturn(42)
        ;

        $child
            ->expects($this->once())
            ->method('convertToFormValue')
            ->with(42, $field, ['type' => 'integer'])
            ->willReturn('submitted')
        ;

        $converter = $this->createConverter($child);
        $config = ['inputType' => 'rows', 'eval' => ['fields' => ['custom' => $field]]];

        $this->assertTrue($converter->supports($config));

        $schema = $converter->getSchema($config, []);
        $rows = $converter->convertToApiValue([['custom' => 'stored']], $config, $schema);

        $this->assertSame(42, $rows[0]->custom);
        $this->assertSame(['_rows' => ['1'], ['custom' => 'submitted']], $converter->convertToFormValue($rows, $config, $schema));
    }

    public function testSupportsNestedRowsAndRejectsUnsupportedChildren(): void
    {
        $converter = $this->createConverter();
        $nested = ['inputType' => 'rows', 'fields' => ['title' => ['inputType' => 'text']]];
        $config = ['inputType' => 'rows', 'fields' => ['children' => $nested]];

        $this->assertTrue($converter->supports($config));

        $schema = $converter->getSchema($config, []);
        $rows = $converter->convertToApiValue([['children' => [['title' => 'Nested']]]], $config, $schema);

        $this->assertSame(['_rows' => ['1'], ['children' => ['_rows' => ['1'], ['title' => 'Nested']]]], $converter->convertToFormValue($rows, $config, $schema));

        $config['fields']['unsupported'] = ['inputType' => 'unknown'];
        $this->assertFalse($converter->supports($config));
        $this->assertFalse($converter->supports(['inputType' => 'text']));
    }

    public function testRespectsChildAccessAndSchemaOverrides(): void
    {
        $config = ['inputType' => 'rows', 'fields' => [
            'secret' => ['inputType' => 'password'],
            'locked' => ['inputType' => 'text', 'eval' => ['readonly' => true], 'api' => ['schema' => ['enum' => ['test']]]],
        ]];

        $converter = $this->createConverter();
        $schema = $converter->getSchema($config, []);

        $this->assertTrue($schema['readOnly']);
        $this->assertSame(['test'], $schema['items']['properties']['locked']['enum']);

        $rows = $converter->convertToApiValue([['secret' => 'hash', 'locked' => 'test']], $config, $schema);
        $this->assertSame('{"locked":"test"}', json_encode($rows[0], JSON_THROW_ON_ERROR));
    }

    public function testTopLevelFieldsTakePrecedenceAndJsonStorageIsDecoded(): void
    {
        $converter = $this->createConverter();
        $config = ['inputType' => 'rows', 'sql' => ['type' => 'json'], 'fields' => ['title' => ['inputType' => 'text']], 'eval' => ['fields' => ['ignored' => ['inputType' => 'unknown']]]];
        $this->assertTrue($converter->supports($config));

        $schema = $converter->getSchema($config, []);
        $this->assertSame(['title'], array_keys($schema['items']['properties']));

        $rows = $converter->convertToApiValue('[{"title":"JSON"}]', $config, $schema);
        $this->assertSame('JSON', $rows[0]->title);

        $validator = new Validator();
        $this->assertTrue($validator->validate($rows, json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR))->isValid());
    }

    public function testConvertsDisabledRowsWithoutDroppingThem(): void
    {
        $config = ['inputType' => 'rows', 'eval' => ['actions' => ['enable']], 'fields' => ['title' => ['inputType' => 'text']]];

        $converter = $this->createConverter();
        $schema = $converter->getSchema($config, []);
        $rows = $converter->convertToApiValue([['title' => 'Disabled', 'enable' => '']], $config, $schema);

        $this->assertFalse($rows[0]->enable);
        $this->assertSame(['_rows' => ['1'], ['title' => 'Disabled', 'enable' => '']], $converter->convertToFormValue($rows, $config, $schema));
    }

    private function createConverter(WidgetConverterInterface|null $custom = null): RowWizardConverter
    {
        $framework = $this->createStub(ContaoFramework::class);
        $converters = new \ArrayObject($custom ? [$custom] : []);
        $converters[] = new CoreWidgetConverter(new DateValueFormatter($framework));
        $registry = new WidgetConverterRegistry($converters);

        $converter = new RowWizardConverter($registry, new DataContainerSchemaFactory($framework, $registry));
        $converters[] = $converter;

        return $converter;
    }
}
