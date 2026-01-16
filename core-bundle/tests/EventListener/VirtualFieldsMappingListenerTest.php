<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\VirtualFieldsMappingListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_File;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class VirtualFieldsMappingListenerTest extends TestCase
{
    #[DataProvider('virtualFieldsMappingProvider')]
    public function testVirtualFieldsMapping(array $fields, array $expected, string $dc = DC_Table::class): void
    {
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'config' => [
                'dataContainer' => $dc,
                'sql' => true,
            ],
            'fields' => $fields,
            'palettes' => ['default' => 'foobar'],
        ];

        (new VirtualFieldsMappingListener())('tl_foobar');

        $this->assertSame($expected, $GLOBALS['TL_DCA']['tl_foobar']['fields']);

        unset($GLOBALS['TL_DCA']);
    }

    public static function virtualFieldsMappingProvider(): iterable
    {
        $defaultSql = ['type' => 'json', 'length' => MySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, 'notnull' => false];
        $textSql = ['type' => 'text', 'length' => MySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, 'notnull' => false];

        yield 'Adds targetColumn and virtualTarget and sql' => [
            [
                'foobar' => ['inputType' => 'text'],
            ],
            [
                'foobar' => ['inputType' => 'text', 'targetColumn' => 'jsonData'],
                'jsonData' => ['virtualTarget' => true, 'sql' => $defaultSql],
            ],
        ];

        yield 'Does not change targetColumn' => [
            [
                'foobar' => ['inputType' => 'text', 'targetColumn' => 'fooData'],
            ],
            [
                'foobar' => ['inputType' => 'text', 'targetColumn' => 'fooData'],
                'fooData' => ['virtualTarget' => true, 'sql' => $defaultSql],
            ],
        ];

        yield 'Does not change sql' => [
            [
                'foobar' => ['inputType' => 'text'],
                'jsonData' => ['sql' => $textSql],
            ],
            [
                'foobar' => ['inputType' => 'text', 'targetColumn' => 'jsonData'],
                'jsonData' => ['sql' => $textSql, 'virtualTarget' => true],
            ],
        ];

        yield 'Does not auto map with existing sql config' => [
            [
                'foobar' => ['inputType' => 'text', 'sql' => []],
            ],
            [
                'foobar' => ['inputType' => 'text', 'sql' => []],
            ],
        ];

        yield 'Does not auto map with existing input_field_callback' => [
            [
                'foobar' => ['inputType' => 'text', 'input_field_callback' => []],
            ],
            [
                'foobar' => ['inputType' => 'text', 'input_field_callback' => []],
            ],
        ];

        yield 'Does not auto map with existing save_callback' => [
            [
                'foobar' => ['inputType' => 'text', 'save_callback' => []],
            ],
            [
                'foobar' => ['inputType' => 'text', 'save_callback' => []],
            ],
        ];

        yield 'Does not auto map for non-DC_Table based DCAs' => [
            [
                'foobar' => ['inputType' => 'text'],
            ],
            [
                'foobar' => ['inputType' => 'text'],
            ],
            DC_File::class,
        ];
    }

    public function testDoesNotMapForNonEditableDcas(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
                'notEditable' => true,
                'sql' => true,
            ],
            'fields' => [
                'foobar' => [
                    'inputType' => 'text',
                ],
            ],
            'palettes' => ['default' => 'foobar'],
        ];

        (new VirtualFieldsMappingListener())('tl_foobar');

        $this->assertSame(['foobar' => ['inputType' => 'text']], $GLOBALS['TL_DCA']['tl_foobar']['fields']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotMapForDcasWithoutPalettes(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
                'sql' => true,
            ],
            'fields' => [
                'foobar' => [
                    'inputType' => 'text',
                ],
            ],
        ];

        (new VirtualFieldsMappingListener())('tl_foobar');

        $this->assertSame(['foobar' => ['inputType' => 'text']], $GLOBALS['TL_DCA']['tl_foobar']['fields']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotMapForDcasDefinedByDoctrineEntity(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
                'sql' => true,
            ],
            'fields' => [
                'foobar' => [
                    'inputType' => 'text',
                ],
            ],
            'palettes' => ['default' => 'foobar'],
        ];

        $classMetaData = $this->createMock(ClassMetadata::class);
        $classMetaData
            ->expects($this->once())
            ->method('getTableName')
            ->willReturn('tl_foobar')
        ;

        $classMetaDataFactory = $this->createMock(ClassMetadataFactory::class);
        $classMetaDataFactory
            ->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$classMetaData])
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($classMetaDataFactory)
        ;

        (new VirtualFieldsMappingListener($entityManager))('tl_foobar');

        $this->assertSame(['foobar' => ['inputType' => 'text']], $GLOBALS['TL_DCA']['tl_foobar']['fields']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotMapForDcasWithoutSqlConfig(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
            ],
            'fields' => [
                'foobar' => [
                    'inputType' => 'text',
                ],
            ],
            'palettes' => ['default' => 'foobar'],
        ];

        (new VirtualFieldsMappingListener())('tl_foobar');

        $this->assertSame(['foobar' => ['inputType' => 'text']], $GLOBALS['TL_DCA']['tl_foobar']['fields']);

        unset($GLOBALS['TL_DCA']);
    }
}
