<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Cron\MessengerCron;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\ProcessUtil;
use GuzzleHttp\Promise\Promise;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Process\Process;

class MessengerCronTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([ProcessUtil::class]);

        parent::tearDown();
    }

    public function testDoesNotRunIfNotOnCli(): void
    {
        $cron = new MessengerCron(new Container(), 'bin/console', []);
        $this->assertNull($cron(Cron::SCOPE_WEB));
    }

    /**
     * @dataProvider autoscalingProvider
     */
    public function testCorrectAmountOfWorkersAreCreated(int $messageCount, int $desiredSize, int $max, array $expectedWorkers): void
    {
        $container = new Container();
        $container->set('prio_normal', $this->mockMessengerTransporter(0, false));
        $container->set('prio_high', $this->mockMessengerTransporter($messageCount, true));

        $cron = new MessengerCron($container, 'bin/console', $this->getWorkers($desiredSize, $max));
        $cron->setPromiseFactory(
            static function (Process $process) {
                return $promise = new Promise(
                    static function () use (&$promise, $process): void {
                        $promise->resolve($process);
                    }
                );
            }
        );
        $promise = $cron(Cron::SCOPE_CLI);

        $processes = [];
        $promise->then(
            static function (array $realProcesses) use (&$processes): void {
                $processes = $realProcesses;
            }
        );

        $promise->wait();
        $this->assertSame($expectedWorkers, $this->unwrapProcesses($processes));
    }

    public function autoscalingProvider(): \Generator
    {
        yield 'Test minimum workers if no message count' => [
            0, // queue empty
            10,
            15,
            [
                'bin/console messenger:consume --time-limit=60 prio_normal',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
            ],
        ];

        yield 'Test minimum workers if we meet exactly the autoscaling target' => [
            10, // exactly desired target
            10,
            15,
            [
                'bin/console messenger:consume --time-limit=60 prio_normal',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
            ],
        ];

        yield 'Test starts a second process if double the desired target (autoscaling)' => [
            20, // exactly double the desired target
            10,
            15,
            [
                'bin/console messenger:consume --time-limit=60 prio_normal',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
            ],
        ];

        yield 'Test starts even more processes (autoscaling)' => [
            60,
            10,
            15,
            [
                'bin/console messenger:consume --time-limit=60 prio_normal',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
            ],
        ];

        yield 'Test respects maximum of 15 workers' => [
            9999, // very long queue
            10,
            15,
            [
                'bin/console messenger:consume --time-limit=60 prio_normal',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
                'bin/console messenger:consume --sleep=5 --time-limit=60 prio_high',
            ],
        ];
    }

    /**
     * @param array<Process> $processes
     */
    private function unwrapProcesses(array $processes): array
    {
        $unwrapped = [];

        foreach ($processes as $process) {
            // Remove the PHP binary path and undo proper quoting (not relevant for this test and required for
            // easier cross-platform CI runs
            $unwrapped[] = str_replace(['\'', '"'], '', trim(strstr($process->getCommandLine(), ' ')));
        }

        return $unwrapped;
    }

    private function mockMessengerTransporter(int $messageCount, bool $hasAutoscaling): MessageCountAwareInterface
    {
        $transport = $this->createMock(MessageCountAwareInterface::class);
        $transport
            ->expects($hasAutoscaling ? $this->once() : $this->never())
            ->method('getMessageCount')
            ->willReturn($messageCount)
        ;

        return $transport;
    }

    private function getWorkers(int $desiredSize, int $max): array
    {
        return [
            [
                'transports' => ['prio_normal'],
                'options' => ['--time-limit=60'],
                'autoscale' => [
                    'enabled' => false,
                ],
            ],
            [
                'transports' => ['prio_high'],
                'options' => ['--sleep=5', '--time-limit=60'],
                'autoscale' => [
                    'desired_size' => $desiredSize,
                    'max' => $max,
                    'enabled' => true,
                ],
            ],
        ];
    }
}
