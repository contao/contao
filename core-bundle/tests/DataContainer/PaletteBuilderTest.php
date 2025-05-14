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

use Contao\CoreBundle\DataContainer\PaletteBuilder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\Input;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class PaletteBuilderTest extends TestCase
{
    #[DataProvider('getCombinerValues')]
    public function testCombiner(array $source, array $expected): void
    {
        $class = new \ReflectionClass(PaletteBuilder::class);
        $method = $class->getMethod('combiner');
        $names = $method->invoke($class->newInstanceWithoutConstructor(), $source);

        $this->assertSame($expected, array_values($names));
    }

    public static function getCombinerValues(): iterable
    {
        return [
            [
                ['foo'],
                ['foo'],
            ],
            [
                ['foo', 'bar'],
                ['bar', 'foobar', 'foo'],
            ],
            [
                ['foo', 'bar', 'baz'],
                ['baz', 'barbaz', 'bar', 'foobar', 'foobarbaz', 'foobaz', 'foo'],
            ],
            [
                ['foo', 0, 'bar'],
                ['bar', '0bar', 'foo0', 'foo0bar', 'foobar', 'foo'],
            ],
        ];
    }

    #[DataProvider('getPaletteValues')]
    public function testPalette(string $expected, array $dca, array|null $currentRecord, array|null $postData = null, bool $editAll = false): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = $dca;

        $dataContainer = $this->createMock(DC_Table::class);
        $dataContainer
            ->method('getCurrentRecord')
            ->with(0, 'tl_foo')
            ->willReturn($currentRecord)
        ;

        $inputAdapter = $this->mockAdapter(['get', 'post']);
        $inputAdapter
            ->method('get')
            ->with('act')
            ->willReturn($editAll ? 'editAll' : 'edit')
        ;

        $inputAdapter
            ->method('post')
            ->willReturnCallback(static fn ($key) => $postData[$key] ?? null)
        ;

        $framework = $this->mockContaoFramework([Input::class => $inputAdapter]);

        $paletteBuilder = new PaletteBuilder($framework, $this->createMock(RequestStack::class), $this->createMock(Security::class), $this->createMock(Connection::class));

        $this->assertSame($expected, $paletteBuilder->getPalette('tl_foo', 0, $dataContainer));

        unset($GLOBALS['TL_DCA']);
    }

    public static function getPaletteValues(): \Generator
    {
        yield 'Use default palette without a selector' => [
            'foo',
            [
                'palettes' => [
                    'default' => 'foo',
                ],
            ],
            null,
        ];

        yield 'Use default if current record is null' => [
            'foo',
            [
                'palettes' => [
                    '__selector__' => ['type'],
                    'default' => 'foo',
                ],
            ],
            null,
        ];

        yield 'Use type of current record' => [
            'bar',
            [
                'palettes' => [
                    '__selector__' => ['type'],
                    'default' => 'foo',
                    'foo' => 'bar',
                ],
            ],
            ['type' => 'foo'],
        ];
    }
}
