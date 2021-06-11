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
use Contao\CoreBundle\Cron\PurgeExpiredDataCron;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Symfony\Bridge\PhpUnit\ClockMock;

class PurgeExpiredDataCronTest extends ContaoTestCase
{
    /**
     * @dataProvider cleanupLogsAndUndoProvider
     */
    public function testCleanupLogsAndUndo(int $undoPeriod, int $logPeriod, int $versionPeriod): void
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

        if ($versionPeriod > 0) {
            $expectedStatements[] = ['DELETE FROM tl_version WHERE tstamp<:tstamp'];
            $expectedExecuteParameters[] = [['tstamp' => $mockedTime - $versionPeriod]];
        }

        $config = $this->mockAdapter(['get']);
        $config
            ->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['undoPeriod'],
                ['logPeriod'],
                ['versionPeriod']
            )
            ->willReturn($undoPeriod, $logPeriod, $versionPeriod)
        ;

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

        $framework = $this->mockContaoFramework([Config::class => $config]);

        $cron = new PurgeExpiredDataCron($framework, $connection);
        $cron->onHourly();

        ClockMock::withClockMock(false);
    }

    public function cleanupLogsAndUndoProvider(): \Generator
    {
        yield 'Do not execute any queries if the periods are configured to 0' => [
            0,
            0,
            0,
        ];

        yield 'Query for the undo period only' => [
            100,
            0,
            0,
        ];

        yield 'Query for the log period only' => [
            0,
            100,
            0,
        ];

        yield 'Query for all periods' => [
            100,
            100,
            100,
        ];
    }
}
