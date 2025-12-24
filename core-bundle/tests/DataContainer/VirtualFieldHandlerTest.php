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

use Contao\CoreBundle\DataContainer\VirtualFieldHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use PHPUnit\Framework\Attributes\DataProvider;

class VirtualFieldHandlerTest extends TestCase
{
    #[DataProvider('virtualFieldsProvider')]
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

        $virtualFieldHandler = new VirtualFieldHandler($contaoFramework);

        $this->assertSame($expanded, $virtualFieldHandler->expandFields($record, $table));
    }

    public static function virtualFieldsProvider(): iterable
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
            ['lorem' => 'foobar'],
            ['moo' => 'bar', 'lorem' => 'ipsum'],
        ];
    }
}
