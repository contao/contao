<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\CoreBundle\DataContainer\ForeignKeyParser\ForeignKeyExpression;
use Contao\CoreBundle\DataContainer\ValueFormatter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValueFormatterTest extends TestCase
{
    #[DataProvider('formatProvider')]
    public function testFormat(mixed $value, array $dca, string $expected, array|null $rawValues = null): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = $dca;

        if (isset($dca['eval']['rgxp'])) {
            $configAdapter = $this->createAdapterMock(['get']);
            $configAdapter
                ->expects($this->atLeastOnce())
                ->method('get')
                ->with($dca['eval']['rgxp'].'Format')
                ->willReturn($dca['eval']['rgxp'].'Format')
            ;

            $dateAdapter = $this->createAdapterMock(['parse']);
            $dateAdapter
                ->expects($this->atLeastOnce())
                ->method('parse')
                ->willReturnMap(array_map(
                    static fn ($v) => [$dca['eval']['rgxp'].'Format', $v, $dca['eval']['rgxp'].': '.date('c', (int) $v)],
                    (array) ($rawValues ?? $value),
                ))
            ;
        } else {
            $configAdapter = $this->createAdapterStub(['get']);
            $dateAdapter = $this->createAdapterStub(['parse']);
        }

        $framework = $this->createContaoFrameworkStub([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $valueFormatter = new ValueFormatter(
            $framework,
            $this->createStub(Connection::class),
            $this->createStub(ForeignKeyParser::class),
            $this->createStub(TranslatorInterface::class),
        );

        $result = $valueFormatter->format('tl_foo', 'foo', $value, null);

        $this->assertSame($expected, $result);

        unset($GLOBALS['TL_DCA']);

        date_default_timezone_set($tz);
    }

    public static function formatProvider(): iterable
    {
        yield 'Plain value' => [
            'foo',
            [],
            'foo',
        ];

        yield 'Plain value with comma' => [
            'foo,bar',
            [],
            'foo,bar',
        ];

        yield 'Plain value with CSV' => [
            'foo,bar',
            ['eval' => ['csv' => ',']],
            'foo, bar',
        ];

        yield 'Plain value with different CSV' => [
            'foo,bar',
            ['eval' => ['csv' => ';']],
            'foo,bar',
        ];

        yield 'Serialized value' => [
            serialize(['foo', 'bar']),
            ['eval' => ['multiple' => true]],
            'foo, bar',
        ];

        yield 'Multiple without serialized value' => [
            'foo,bar',
            ['eval' => ['multiple' => true]],
            'foo,bar',
        ];

        yield 'Date' => [
            '1764689390',
            ['eval' => ['rgxp' => 'date']],
            'date: 2025-12-02T15:29:50+00:00',
        ];

        yield 'Time' => [
            '1764689390',
            ['eval' => ['rgxp' => 'time']],
            'time: 2025-12-02T15:29:50+00:00',
        ];

        yield 'Datim' => [
            '1764689390',
            ['eval' => ['rgxp' => 'datim']],
            'datim: 2025-12-02T15:29:50+00:00',
        ];

        yield 'CSV dates' => [
            '1764689390,1764689391',
            ['eval' => ['csv' => ',', 'rgxp' => 'date']],
            'date: 2025-12-02T15:29:50+00:00, date: 2025-12-02T15:29:51+00:00',
            ['1764689390', '1764689391'],
        ];

        yield 'Serialized dates' => [
            serialize(['1764689390', '1764689391']),
            ['eval' => ['multiple' => true, 'rgxp' => 'date']],
            'date: 2025-12-02T15:29:50+00:00, date: 2025-12-02T15:29:51+00:00',
            ['1764689390', '1764689391'],
        ];

        yield 'From reference' => [
            'foo',
            ['reference' => ['foo' => 'bar', 'bar' => 'baz']],
            'bar',
        ];

        yield 'From CSV reference' => [
            'foo,bar',
            ['reference' => ['foo' => 'bar', 'bar' => 'baz'], 'eval' => ['csv' => ',']],
            'bar, baz',
        ];

        yield 'From multiple reference' => [
            serialize(['foo', 'bar']),
            ['reference' => ['foo' => 'bar', 'bar' => 'baz'], 'eval' => ['multiple' => true]],
            'bar, baz',
        ];

        yield 'From array reference' => [
            'foo',
            ['reference' => ['foo' => ['Label', 'Description']]],
            'Label',
        ];

        yield 'From options' => [
            'foo',
            ['options' => ['foo' => 'bar', 'bar' => 'baz']],
            'bar',
        ];

        yield 'From CSV options' => [
            'foo,bar',
            ['options' => ['foo' => 'bar', 'bar' => 'baz'], 'eval' => ['csv' => ',']],
            'bar, baz',
        ];

        yield 'From multiple options' => [
            serialize(['foo', 'bar']),
            ['options' => ['foo' => 'bar', 'bar' => 'baz'], 'eval' => ['multiple' => true]],
            'bar, baz',
        ];

        yield 'From nested options' => [
            serialize(['foo', 'bar']),
            ['options' => ['Group1' => ['foo' => 'bar'], 'Group2' => ['bar' => 'baz']], 'eval' => ['multiple' => true]],
            'bar, baz',
        ];

        yield 'Reference wins over options' => [
            'foo',
            [
                'options' => ['foo' => 'notbar', 'bar' => 'notbaz'],
                'reference' => ['foo' => 'bar', 'bar' => 'baz'],
            ],
            'bar',
        ];
    }

    #[DataProvider('formatBooleanProvider')]
    public function testFormatBoolean(array $dca, mixed $value, string $expected, bool $translates = true): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = $dca;

        $framework = $this->createContaoFrameworkStub([
            Date::class => $this->createAdapterStub(['parse']),
            Config::class => $this->createAdapterStub(['get']),
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($translates ? $this->once() : $this->never())
            ->method('trans')
            ->with('MSC.'.$expected)
            ->willReturn($expected)
        ;

        $valueFormatter = new ValueFormatter(
            $framework,
            $this->createStub(Connection::class),
            $this->createStub(ForeignKeyParser::class),
            $translator,
        );

        $result = $valueFormatter->format('tl_foo', 'foo', $value, null);

        $this->assertSame($expected, $result);

        unset($GLOBALS['TL_DCA']);
    }

    public static function formatBooleanProvider(): iterable
    {
        yield [
            ['eval' => ['isBoolean' => true]],
            '1',
            'yes',
        ];

        yield [
            ['eval' => ['isBoolean' => true]],
            'asdf',
            'yes',
        ];

        yield [
            ['eval' => ['isBoolean' => true]],
            true,
            'yes',
        ];

        yield [
            ['eval' => ['isBoolean' => true]],
            '0',
            'no',
        ];

        yield [
            ['eval' => ['isBoolean' => true]],
            '',
            'no',
        ];

        yield [
            ['eval' => ['isBoolean' => true]],
            false,
            'no',
        ];

        yield [
            ['eval' => ['isBoolean' => false]],
            '1',
            '1',
            false,
        ];

        yield [
            ['eval' => ['isBoolean' => false]],
            '0',
            '0',
            false,
        ];

        yield [
            ['inputType' => 'checkbox'],
            '1',
            'yes',
        ];

        yield [
            ['inputType' => 'checkbox'],
            '0',
            'no',
        ];

        yield [
            ['inputType' => 'checkbox', 'eval' => ['multiple' => true]],
            '1',
            '1',
            false,
        ];

        yield [
            ['inputType' => 'checkbox', 'eval' => ['multiple' => true]],
            '0',
            '0',
            false,
        ];
    }

    public function testFormatListingWithoutForeignKey(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = [];

        $configAdapter = $this->createAdapterStub(['get']);
        $dateAdapter = $this->createAdapterStub(['parse']);

        $framework = $this->createContaoFrameworkStub([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $valueFormatter = new ValueFormatter(
            $framework,
            $this->createStub(Connection::class),
            $this->createStub(ForeignKeyParser::class),
            $this->createStub(TranslatorInterface::class),
        );

        $result = $valueFormatter->formatListing('tl_foo', 'foo', ['foo' => 'bar'], $this->createStub(DataContainer::class));

        $this->assertSame('bar', $result);

        unset($GLOBALS['TL_DCA']);
    }

    public function testFormatListingWithForeignKey(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = [];

        $configAdapter = $this->createAdapterStub(['get']);
        $dateAdapter = $this->createAdapterStub(['parse']);

        $framework = $this->createContaoFrameworkStub([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT `name` FROM tl_foo WHERE id=?', [42])
            ->willReturn('bar')
        ;

        $foreignKeyParser = $this->createMock(ForeignKeyParser::class);
        $foreignKeyParser
            ->expects($this->once())
            ->method('parse')
            ->willReturnCallback(static fn ($v) => (new ForeignKeyExpression('tl_foo', '`name`'))->withColumnName('name')->withKey('foo'))
        ;

        $valueFormatter = new ValueFormatter(
            $framework,
            $connection,
            $foreignKeyParser,
            $this->createStub(TranslatorInterface::class),
        );

        $result = $valueFormatter->formatListing(
            'tl_foo',
            'foo:tl_foo.name',
            ['foo' => 42],
            $this->createStub(DataContainer::class),
        );

        $this->assertSame('bar', $result);

        unset($GLOBALS['TL_DCA']);
    }

    #[DataProvider('formatFilterOptionsProvider')]
    public function testFormatFilterOptions(array $dca, array $values, array $expected): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = $dca;

        $configAdapter = $this->createAdapterStub(['get']);
        $dateAdapter = $this->createAdapterStub(['parse']);

        $framework = $this->createContaoFrameworkStub([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $valueFormatter = new ValueFormatter(
            $framework,
            $this->createStub(Connection::class),
            $this->createStub(ForeignKeyParser::class),
            $this->createStub(TranslatorInterface::class),
        );

        $result = $valueFormatter->formatFilterOptions('tl_foo', 'foo', $values, $this->createStub(DataContainer::class));

        $this->assertSame($expected, $result);

        unset($GLOBALS['TL_DCA']);
    }

    public static function formatFilterOptionsProvider(): iterable
    {
        yield 'Empty values' => [
            [],
            [],
            [],
        ];

        yield 'Values' => [
            [],
            ['a', 'b', 'c', 'd'],
            [
                ['value' => 'a', 'label' => 'a'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'c', 'label' => 'c'],
                ['value' => 'd', 'label' => 'd'],
            ],
        ];

        yield 'Multiple values' => [
            ['eval' => ['multiple' => true]],
            [
                serialize(['a', 'b']),
                serialize(['c', 'd']),
            ],
            [
                ['value' => 'a', 'label' => 'a'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'c', 'label' => 'c'],
                ['value' => 'd', 'label' => 'd'],
            ],
        ];

        yield 'Removes duplicate values from multiple values' => [
            ['eval' => ['multiple' => true]],
            [
                serialize(['a', 'b']),
                serialize(['b', 'd']),
            ],
            [
                ['value' => 'a', 'label' => 'a'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'd', 'label' => 'd'],
            ],
        ];

        yield 'CSV values' => [
            ['eval' => ['csv' => ',']],
            ['a,b', 'c,d'],
            [
                ['value' => 'a', 'label' => 'a'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'c', 'label' => 'c'],
                ['value' => 'd', 'label' => 'd'],
            ],
        ];

        yield 'Removes duplicate values from CSV values' => [
            ['eval' => ['csv' => ',']],
            ['a,b', 'b,c'],
            [
                ['value' => 'a', 'label' => 'a'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'c', 'label' => 'c'],
            ],
        ];

        yield 'Reverse sorting for SORT_INITIAL_LETTER_DESC' => [
            ['flag' => DataContainer::SORT_INITIAL_LETTER_DESC],
            ['a', 'b', 'c', 'd'],
            [
                ['value' => 'd', 'label' => 'd'],
                ['value' => 'c', 'label' => 'c'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'a', 'label' => 'a'],
            ],
        ];

        yield 'Reverse sorting for SORT_INITIAL_LETTERS_DESC' => [
            ['flag' => DataContainer::SORT_INITIAL_LETTERS_DESC],
            ['a', 'b', 'c', 'd'],
            [
                ['value' => 'd', 'label' => 'd'],
                ['value' => 'c', 'label' => 'c'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'a', 'label' => 'a'],
            ],
        ];

        yield 'Reverse sorting for SORT_DESC' => [
            ['flag' => DataContainer::SORT_DESC],
            ['a', 'b', 'c', 'd'],
            [
                ['value' => 'd', 'label' => 'd'],
                ['value' => 'c', 'label' => 'c'],
                ['value' => 'b', 'label' => 'b'],
                ['value' => 'a', 'label' => 'a'],
            ],
        ];
    }

    #[DataProvider('formatDateFilterOptionsProvider')]
    public function testFormatDateFilterOptions(int $flag, string $format, array $values, array $expected): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = ['flag' => $flag];

        $configAdapter = $this->createAdapterStub(['get']);
        $configAdapter
            ->method('get')
            ->with('dateFormat')
            ->willReturn('Y-m-d')
        ;

        $dateAdapter = $this->createAdapterStub(['parse']);
        $dateAdapter
            ->method('parse')
            ->willReturnMap(array_map(
                static fn ($v) => ['Y-m-d', $v, date($format, (int) $v)],
                $values,
            ))
        ;

        $framework = $this->createContaoFrameworkStub([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $valueFormatter = new ValueFormatter(
            $framework,
            $this->createStub(Connection::class),
            $this->createStub(ForeignKeyParser::class),
            $translator,
        );

        $result = $valueFormatter->formatFilterOptions('tl_foo', 'foo', $values, $this->createStub(DataContainer::class));

        $this->assertSame($expected, $result);

        unset($GLOBALS['TL_DCA']);
    }

    public static function formatDateFilterOptionsProvider(): iterable
    {
        $values = [
            '1764689000', // 2025-12-02T15:23:20+00:00
            '1764689390', // 2025-12-02T15:29:50+00:00
            '176468900', // 1975-08-05T12:08:20+00:00
        ];

        yield [
            DataContainer::SORT_DAY_ASC,
            'Y-m-d',
            $values,
            [
                ['value' => '176468900', 'label' => '1975-08-05'],
                ['value' => '1764689000', 'label' => '2025-12-02'],
                ['value' => '1764689390', 'label' => '2025-12-02'],
            ],
        ];

        yield [
            DataContainer::SORT_DAY_BOTH,
            'Y-m-d',
            $values,
            [
                ['value' => '176468900', 'label' => '1975-08-05'],
                ['value' => '1764689000', 'label' => '2025-12-02'],
                ['value' => '1764689390', 'label' => '2025-12-02'],
            ],
        ];

        yield [
            DataContainer::SORT_DAY_DESC,
            'Y-m-d',
            $values,
            [
                ['value' => '1764689390', 'label' => '2025-12-02'],
                ['value' => '1764689000', 'label' => '2025-12-02'],
                ['value' => '176468900', 'label' => '1975-08-05'],
            ],
        ];

        yield [
            DataContainer::SORT_MONTH_ASC,
            'Y-m',
            $values,
            [
                ['value' => '176468900', 'label' => '1975-08'],
                ['value' => '1764689000', 'label' => '2025-12'],
                ['value' => '1764689390', 'label' => '2025-12'],
            ],
        ];

        yield [
            DataContainer::SORT_MONTH_BOTH,
            'Y-m',
            $values,
            [
                ['value' => '176468900', 'label' => '1975-08'],
                ['value' => '1764689000', 'label' => '2025-12'],
                ['value' => '1764689390', 'label' => '2025-12'],
            ],
        ];

        yield [
            DataContainer::SORT_MONTH_DESC,
            'Y-m',
            $values,
            [
                ['value' => '1764689390', 'label' => '2025-12'],
                ['value' => '1764689000', 'label' => '2025-12'],
                ['value' => '176468900', 'label' => '1975-08'],
            ],
        ];

        yield [
            DataContainer::SORT_YEAR_ASC,
            'Y-m',
            $values,
            [
                ['value' => '176468900', 'label' => '1975'],
                ['value' => '1764689000', 'label' => '2025'],
                ['value' => '1764689390', 'label' => '2025'],
            ],
        ];

        yield [
            DataContainer::SORT_YEAR_BOTH,
            'Y-m',
            $values,
            [
                ['value' => '176468900', 'label' => '1975'],
                ['value' => '1764689000', 'label' => '2025'],
                ['value' => '1764689390', 'label' => '2025'],
            ],
        ];

        yield [
            DataContainer::SORT_YEAR_DESC,
            'Y-m',
            $values,
            [
                ['value' => '1764689390', 'label' => '2025'],
                ['value' => '1764689000', 'label' => '2025'],
                ['value' => '176468900', 'label' => '1975'],
            ],
        ];
    }
}
