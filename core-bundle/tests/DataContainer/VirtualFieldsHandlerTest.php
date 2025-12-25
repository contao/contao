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

use Contao\CoreBundle\DataContainer\VirtualFieldsHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\StringUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

class VirtualFieldsHandlerTest extends TestCase
{
    #[DataProvider('expandsProvider')]
    public function testExpandsVirtualFields(array $record, array $targets, array $fields, array $expanded): void
    {
        $table = 'tl_foobar';

        $dcaExtractor = $this->createMock(DcaExtractor::class);
        $dcaExtractor
            ->expects($this->once())
            ->method('getVirtualTargets')
            ->willReturn($targets)
        ;

        $dcaExtractor
            ->expects($this->once())
            ->method('getVirtualFields')
            ->willReturn($fields)
        ;

        $contaoFramework = $this->createContaoFrameworkStub([], [DcaExtractor::class => $dcaExtractor]);

        $virtualFieldsHandler = new VirtualFieldsHandler($contaoFramework);

        $this->assertSame($expanded, $virtualFieldsHandler->expandFields($record, $table));
    }

    public static function expandsProvider(): iterable
    {
        yield 'Expands virtual field' => [
            ['foobar' => json_encode(['lorem' => 'ipsum']), 'moo' => 'bar'],
            ['foobar'],
            ['lorem' => 'foobar'],
            ['moo' => 'bar', 'lorem' => 'ipsum'],
        ];

        yield 'Does not expand non-storage fields' => [
            ['foobar' => json_encode(['lorem' => 'ipsum']), 'moo' => 'bar'],
            [],
            ['lorem' => 'nope'],
            ['foobar' => json_encode(['lorem' => 'ipsum']), 'moo' => 'bar'],
        ];

        yield 'Does not set non-virtual fields' => [
            ['foobar' => json_encode(['lorem' => 'ipsum', 'dolor' => 'sit']), 'moo' => 'bar'],
            ['foobar'],
            ['dolor' => 'foobar'],
            ['moo' => 'bar', 'dolor' => 'sit'],
        ];
    }

    #[DataProvider('combinesProvider')]
    public function testCombinesVirtualFields(array $record, array $targets, array $fields, array $combined): void
    {
        $table = 'tl_foobar';

        $dcaExtractor = $this->createMock(DcaExtractor::class);
        $dcaExtractor
            ->expects($this->once())
            ->method('getVirtualFields')
            ->willReturn($fields)
        ;

        $contaoFramework = $this->createContaoFrameworkStub([], [DcaExtractor::class => $dcaExtractor]);

        $virtualFieldsHandler = new VirtualFieldsHandler($contaoFramework);

        $this->assertSame($combined, $virtualFieldsHandler->combineFields($record, $table));
    }

    public static function combinesProvider(): iterable
    {
        yield 'Combines virtual fields' => [
            ['field1' => 'Lorem', 'field2' => 'Ipsum', 'field3' => 'Dolor', 'field4' => 'Sit', 'foo' => 'bar'],
            ['target1', 'target2'],
            ['field1' => 'target1', 'field2' => 'target1', 'field3' => 'target2', 'field4' => 'target2'],
            [
                'foo' => 'bar',
                'target1' => [
                    'field1' => 'Lorem',
                    'field2' => 'Ipsum',
                ],
                'target2' => [
                    'field3' => 'Dolor',
                    'field4' => 'Sit',
                ],
            ],
        ];

        yield 'Ignores virtual field not present in record' => [
            ['field1' => 'Lorem', 'foo' => 'bar'],
            ['target'],
            ['field1' => 'target', 'field2' => 'target'],
            [
                'foo' => 'bar',
                'target' => [
                    'field1' => 'Lorem',
                ],
            ],
        ];

        $uuid1 = Uuid::v1()->toRfc4122();
        $uuid2 = Uuid::v1()->toRfc4122();

        yield 'Converts binary UUIDs to string UUIDs' => [
            ['field' => StringUtil::uuidToBin($uuid1), 'foo' => 'bar'],
            ['target'],
            ['field' => 'target'],
            [
                'foo' => 'bar',
                'target' => [
                    'field' => $uuid1,
                ],
            ],
        ];

        yield 'Converts serialized binary UUIDs to string UUIDs' => [
            [
                'field1' => serialize([StringUtil::uuidToBin($uuid1), StringUtil::uuidToBin($uuid2)]),
                'field2' => [StringUtil::uuidToBin($uuid1), StringUtil::uuidToBin($uuid2)],
                'foo' => 'bar',
            ],
            ['target'],
            ['field1' => 'target', 'field2' => 'target'],
            [
                'foo' => 'bar',
                'target' => [
                    'field1' => serialize([$uuid1, $uuid2]),
                    'field2' => [$uuid1, $uuid2],
                ],
            ],
        ];
    }
}
