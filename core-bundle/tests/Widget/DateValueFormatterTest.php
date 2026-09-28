<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Widget;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\Date;
use Contao\TestCase\ContaoTestCase;

class DateValueFormatterTest extends ContaoTestCase
{
    public function testResolvesTheFormatForEachCall(): void
    {
        $adapter = $this->createAdapterMock(['getFormatFromRgxp']);
        $adapter
            ->expects($this->exactly(3))
            ->method('getFormatFromRgxp')
            ->willReturnMap([
                ['date', 'd.m.Y'],
                ['time', 'H:i'],
                ['datim', 'd.m.Y H:i'],
            ])
        ;

        $date = $this->createMock(Date::class);
        $date
            ->expects($this->exactly(3))
            ->method('__get')
            ->willReturnMap([
                ['date', '22.09.2026'],
                ['time', '12:34'],
                ['datim', '22.09.2026 12:34'],
            ])
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->exactly(3))
            ->method('initialize')
        ;

        $framework
            ->expects($this->exactly(3))
            ->method('getAdapter')
            ->with(Date::class)
            ->willReturn($adapter)
        ;
        $framework
            ->expects($this->exactly(3))
            ->method('createInstance')
            ->willReturnCallback(
                function (string $class, array $args) use ($date): Date {
                    $this->assertSame(Date::class, $class);
                    $this->assertSame(1234567890, $args[0]);
                    $this->assertContains($args[1], ['d.m.Y', 'H:i', 'd.m.Y H:i']);

                    return $date;
                },
            )
        ;

        $formatter = new DateValueFormatter($framework);

        $this->assertSame('22.09.2026', $formatter->format(1234567890, 'date'));
        $this->assertSame('12:34', $formatter->format(1234567890, 'time'));
        $this->assertSame('22.09.2026 12:34', $formatter->format(1234567890, 'datim'));
    }

    public function testSkipsEmptyValuesAndNonDateFields(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $formatter = new DateValueFormatter($framework);

        $this->assertNull($formatter->format('', 'date'));
        $this->assertNull($formatter->format('example', 'email'));
    }

    public function testReturnsNullForAnUnconvertibleDate(): void
    {
        $adapter = $this->createAdapterStub(['getFormatFromRgxp']);
        $adapter
            ->method('getFormatFromRgxp')
            ->willReturn('d.m.Y')
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('getAdapter')
            ->willReturn($adapter)
        ;

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(Date::class, ['invalid', 'd.m.Y'])
            ->willThrowException(new \OutOfBoundsException())
        ;

        $this->assertNull(new DateValueFormatter($framework)->format('invalid', 'date'));
    }
}
