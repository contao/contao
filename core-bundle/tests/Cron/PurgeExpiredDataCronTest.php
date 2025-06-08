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
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Clock\MockClock;

class PurgeExpiredDataCronTest extends ContaoTestCase
{
    #[DataProvider('cleanupLogsAndUndoProvider')]
    public function testCleanupLogsAndUndo(int $undoPeriod, int $logPeriod, int $versionPeriod): void
    {
        $mockedTime = 1142164800;

        $expectedStatements = [];

        if ($undoPeriod > 0) {
            $expectedStatements[] = [
                'DELETE FROM tl_undo WHERE tstamp < :tstamp',
                ['tstamp' => $mockedTime - $undoPeriod],
                ['tstamp' => Types::INTEGER],
            ];
        }

        if ($logPeriod > 0) {
            $expectedStatements[] = [
                'DELETE FROM tl_log WHERE tstamp < :tstamp',
                ['tstamp' => $mockedTime - $logPeriod],
                ['tstamp' => Types::INTEGER],
            ];
        }

        if ($versionPeriod > 0) {
            $expectedStatements[] = [
                'DELETE FROM tl_version WHERE tstamp < :tstamp',
                ['tstamp' => $mockedTime - $versionPeriod],
                ['tstamp' => Types::INTEGER],
            ];
        }

        $config = $this->mockAdapter(['get']);
        $config
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['undoPeriod', $undoPeriod],
                ['logPeriod', $logPeriod],
                ['versionPeriod', $versionPeriod],
            ])
        ;

        $connection = $this->createMock(Connection::class);
        $matcher = $this->exactly(\count($expectedStatements));
        $connection
            ->expects($matcher)
            ->method('executeStatement')
            ->with($this->callback(
                static fn (...$parameters): bool => $expectedStatements[$matcher->numberOfInvocations() - 1] === $parameters,
            ))
        ;

        $framework = $this->mockContaoFramework([Config::class => $config]);

        $cron = new PurgeExpiredDataCron($framework, $connection, new MockClock('@'.$mockedTime));
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

        yield 'Query for all periods' => [
            100,
            100,
            100,
        ];
    }
}
