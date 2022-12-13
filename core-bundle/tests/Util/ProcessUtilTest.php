<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\ProcessUtil;
use GuzzleHttp\Promise\Is;
use GuzzleHttp\Promise\RejectionException;
use Symfony\Component\Process\Process;

class ProcessUtilTest extends TestCase
{
    /**
     * @dataProvider promiseTestProvider
     */
    public function testPromise(bool $successful, bool $autostart): void
    {
        $process = $this->mockProcess($successful, $autostart);
        $promise = ProcessUtil::createPromise($process, $autostart);

        $this->assertTrue(Is::pending($promise));

        if ($successful) {
            $this->assertSame('Success!', $promise->wait());
        } else {
            try {
                $promise->wait();
            } catch (\Exception $exception) {
                $this->assertInstanceOf(RejectionException::class, $exception);
                $this->assertSame('Error!', $exception->getReason());
            }
        }

        $this->assertSame($successful, Is::fulfilled($promise));
        $this->assertSame(!$successful, Is::rejected($promise));
    }

    public function promiseTestProvider(): \Generator
    {
        yield 'Successful, autostart promise' => [true, true];
        yield 'Successful, non-autostart promise' => [true, false];
        yield 'Unsuccessful, autostart promise' => [false, true];
        yield 'Unsuccessful,non-autostart promise' => [false, false];
    }

    private function mockProcess(bool $isSuccessful, bool $autostart): Process
    {
        $process = $this->createMock(Process::class);
        $process
            ->expects($autostart ? $this->once() : $this->never())
            ->method('start')
        ;
        $process
            ->expects($this->once())
            ->method('wait')
        ;
        $process
            ->method('isSuccessful')
            ->willReturn($isSuccessful)
        ;

        if ($isSuccessful) {
            $process
                ->expects($this->once())
                ->method('getOutput')
                ->willReturn('Success!')
            ;
        } else {
            $process
                ->expects($this->never())
                ->method('getOutput')
            ;
        }

        if (!$isSuccessful) {
            $process
                ->expects($this->once())
                ->method('getErrorOutput')
                ->willReturn('Error!')
            ;
        } else {
            $process
                ->expects($this->never())
                ->method('getErrorOutput')
            ;
        }

        return $process;
    }
}
