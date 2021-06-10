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

use Contao\Config;
use Contao\CoreBundle\Cron\CleanupCron;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Symfony\Bridge\PhpUnit\ClockMock;

class CleanupCronTest extends ContaoTestCase
{
    /**
     * @dataProvider cleanupLogsAndUndoProvider
     */
    public function testCleanupLogsAndUndo(int $undoPeriod, int $logPeriod): void
    {
        $mockedTime = 1142164800;
        ClockMock::withClockMock($mockedTime);

        $expectedStatements = [];
        $expectedExecuteParameters = [];

        if ($undoPeriod > 0) {
            $expectedStatements[] = ['DELETE FROM tl_undo WHERE tstamp<:tstamp'];
            $expectedExecuteParameters[] = [['tstamp' => $mockedTime - $undoPeriod]];
        }

        if ($logPeriod > 0) {
            $expectedStatements[] = ['DELETE FROM tl_log WHERE tstamp<:tstamp'];
            $expectedExecuteParameters[] = [['tstamp' => $mockedTime - $logPeriod]];
        }

        $config = $this->mockAdapter(['get']);
        $config
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['undoPeriod'],
                ['logPeriod']
            )
            ->willReturn($undoPeriod, $logPeriod)
        ;

        $framework = $this->mockContaoFramework([Config::class => $config]);

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->exactly(\count($expectedExecuteParameters)))
            ->method('executeStatement')
            ->withConsecutive(...$expectedExecuteParameters)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(\count($expectedStatements)))
            ->method('prepare')
            ->withConsecutive(...$expectedStatements)
            ->willReturn($statement)
        ;

        $cron = new CleanupCron($framework, $connection);
        $cron->onDaily();

        ClockMock::withClockMock(false);
    }

    public function cleanupLogsAndUndoProvider(): \Generator
    {
        yield 'Do not execute any queries if the periods are configured to 0' => [
            0,
            0,
        ];

        yield 'Query for the undo period only' => [
            100,
            0,
        ];

        yield 'Query for the log period only' => [
            0,
            100,
        ];

        yield 'Query for both, undo and log periods' => [
            100,
            100,
        ];
    }
}
