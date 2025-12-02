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
use Contao\CoreBundle\DataContainer\ValueFormatter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValueFormatterTest extends TestCase
{
    #[DataProvider('formatProvider')]
    public function testFormat(mixed $value, array $dca, string $expected, array|null $rawValues = null): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = $dca;

        $configAdapter = $this->mockAdapter(['get']);
        $dateAdapter = $this->mockAdapter(['parse']);

        if (isset($dca['eval']['rgxp'])) {
            $configAdapter
                ->expects($this->atLeastOnce())
                ->method('get')
                ->with($dca['eval']['rgxp'].'Format')
                ->willReturn($dca['eval']['rgxp'].'Format')
            ;

            $dateAdapter
                ->expects($this->atLeastOnce())
                ->method('parse')
                ->willReturnMap(array_map(
                    static fn ($v) => [$dca['eval']['rgxp'].'Format', $v, $dca['eval']['rgxp'].': '.date('c', (int) $v)],
                    (array) ($rawValues ?? $value),
                ))
            ;
        }

        $framework = $this->mockContaoFramework([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $valueFormatter = new ValueFormatter(
            $framework,
            $this->createMock(Connection::class),
            $this->createMock(TranslatorInterface::class),
        );

        $result = $valueFormatter->format('tl_foo', 'foo', $value, null);

        $this->assertSame($expected, $result);

        unset($GLOBALS['TL_DCA']);
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

        $framework = $this->mockContaoFramework([
            Date::class => $this->mockAdapter(['parse']),
            Config::class => $this->mockAdapter(['get']),
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
            $this->createMock(Connection::class),
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

        $configAdapter = $this->mockAdapter(['get']);
        $dateAdapter = $this->mockAdapter(['parse']);

        $framework = $this->mockContaoFramework([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $valueFormatter = new ValueFormatter(
            $framework,
            $this->createMock(Connection::class),
            $this->createMock(TranslatorInterface::class),
        );

        $result = $valueFormatter->formatListing('tl_foo', 'foo', ['foo' => 'bar'], $this->createMock(DataContainer::class));

        $this->assertSame('bar', $result);

        unset($GLOBALS['TL_DCA']);
    }

    public function testFormatListingWithForeignKey(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['fields']['foo'] = [];

        $configAdapter = $this->mockAdapter(['get']);
        $dateAdapter = $this->mockAdapter(['parse']);

        $framework = $this->mockContaoFramework([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $connection = $this->mockConnection();
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT `name` FROM tl_foo WHERE id=?', [42])
            ->willReturn('bar')
        ;

        $valueFormatter = new ValueFormatter(
            $framework,
            $connection,
            $this->createMock(TranslatorInterface::class),
        );

        $result = $valueFormatter->formatListing(
            'tl_foo',
            'foo:tl_foo.name',
            ['foo' => 42],
            $this->createMock(DataContainer::class),
        );

        $this->assertSame('bar', $result);

        unset($GLOBALS['TL_DCA']);
    }

    private function mockConnection(): Connection&MockObject
    {
        $databasePlatform = $this->createMock(AbstractPlatform::class);
        $databasePlatform
            ->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($v) => '`'.$v.'`')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($databasePlatform)
        ;

        return $connection;
    }
}
