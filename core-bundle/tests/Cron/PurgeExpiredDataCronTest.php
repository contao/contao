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

use Contao\CoreBundle\Cron\PurgeExpiredDataCron;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Clock\MockClock;

class PurgeExpiredDataCronTest extends ContaoTestCase
{
    #[DataProvider('cleanupLogsAndUndoProvider')]
    public function testCleanupLogsAndUndo(int $undoPeriod, int $logPeriod, int $versionPeriod): void
    {
        $mockedTime = 1142164800;

        $expectedStatements = $this->getExpectedStatements($mockedTime, [
            'tl_undo' => $undoPeriod,
            'tl_log' => $logPeriod,
            'tl_version' => $versionPeriod,
        ]);

        $connection = $this->createMock(Connection::class);
        $matcher = $this->exactly(\count($expectedStatements));
        $connection
            ->expects($matcher)
            ->method('executeStatement')
            ->with($this->callback(
                static fn (...$parameters): bool => $expectedStatements[$matcher->numberOfInvocations() - 1] === $parameters,
            ))
        ;

        $cron = new PurgeExpiredDataCron(
            $this->createContaoFrameworkStub(),
            $connection,
            new MockClock('@'.$mockedTime),
            $undoPeriod,
            $versionPeriod,
            $logPeriod,
        );
        $cron->onHourly();
    }

    public static function cleanupLogsAndUndoProvider(): iterable
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

        yield 'Query for the version period only' => [
            0,
            0,
            100,
        ];

        yield 'Query for all periods' => [
            100,
            200,
            300,
        ];
    }

    private function getExpectedStatements(int $timestamp, array $periods): array
    {
        $statements = [];

        foreach ($periods as $table => $period) {
            if ($period > 0) {
                $statements[] = [
                    "DELETE FROM $table WHERE tstamp < :tstamp",
                    ['tstamp' => $timestamp - $period],
                    ['tstamp' => Types::INTEGER],
                ];
            }
        }

        return $statements;
    }
}
