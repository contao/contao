<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\StartStopValidationListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartStopValidationListenerTest extends TestCase
{
    /**
     * @dataProvider valueProvider
     */
    public function testValidatesStartStop(array $values, array $currentRecord = null, bool $expectException = false): void
    {
        $translator = $this->createMock(TranslatorInterface::class);

        if ($expectException) {
            $this->expectException(\RuntimeException::class);
            $translator
                ->expects($this->once())
                ->method('trans')
                ->with('ERR.startStop', [], 'contao_default')
                ->willReturnArgument(0)
            ;
        } else {
            $translator
                ->expects($this->never())
                ->method('trans')
            ;
        }

        $dc = $this->createMock(DataContainer::class);

        if (null === $currentRecord) {
            $dc
                ->expects($this->never())
                ->method('getCurrentRecord')
            ;
        } else {
            $dc
                ->expects($this->once())
                ->method('getCurrentRecord')
                ->willReturn($currentRecord)
            ;
        }

        $listener = new StartStopValidationListener($translator);
        $listener($values, $dc);
    }

    public function valueProvider(): \Generator
    {
        yield 'Start and stop is not submitted' => [
            ['foo' => 'bar'],
        ];

        yield 'Start is empty' => [
            ['start' => '', 'stop' => time()],
        ];

        yield 'Stop is empty' => [
            ['start' => time(), 'stop' => ''],
        ];

        yield 'Start is later than stop' => [
            ['start' => time(), 'stop' => time() + 1],
        ];

        yield 'Throws exception if stop is before start' => [
            ['start' => time() + 1, 'stop' => time()],
            null,
            true,
        ];

        yield 'Uses start from DataContainer' => [
            ['stop' => time() + 1],
            ['start' => time()],
        ];

        yield 'Uses stop from DataContainer' => [
            ['start' => time() + 1],
            ['stop' => time()],
            true,
        ];
    }
}
