<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\Database;
use Contao\Database\Result;
use Contao\Database\Statement;
use Contao\DC_Table;

class DC_TableTest extends TestCase
{
    /**
     * @dataProvider getPalette
     */
    public function testGetPalette(array $dca, array $row, string $expected): void
    {
        $result = new Result([$row], '');

        $statement = $this->createMock(Statement::class);

        $statement
            ->method('limit')
            ->willReturn($statement)
        ;

        $statement
            ->method('execute')
            ->willReturn($result)
        ;

        $database = $this->createMock(Database::class);
        $database
            ->method('prepare')
            ->willReturn($statement)
        ;

        $dataContainer = (new \ReflectionClass(DC_Table::class))->newInstanceWithoutConstructor();
        $dataContainer->Database = $database;

        /** @phpstan-ignore-next-line */
        $dataContainer->strTable = 'tl_test';

        $GLOBALS['TL_DCA']['tl_test'] = $dca;

        $this->assertSame($expected, $dataContainer->getPalette());
    }

    public function getPalette(): array
    {
        return [
            [
                [
                    'palettes' => [
                        '__selector__' => ['fieldA', 'fieldB', 'fieldC'],
                        'default' => 'paletteDefault',
                        'valueA' => 'paletteA',
                        'valueAvalueC' => 'paletteAC',
                    ],
                    'fields' => [
                        'fieldA' => ['inputType' => 'text'],
                        'fieldB' => ['inputType' => 'text'],
                        'fieldC' => ['inputType' => 'text'],
                    ],
                ],
                [
                    'fieldA' => 'valueA',
                    'fieldB' => null,
                    'fieldC' => 'valueC',
                ],
                'paletteAC',
            ],
        ];
    }
}
