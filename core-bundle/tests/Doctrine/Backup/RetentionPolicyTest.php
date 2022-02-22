<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\RetentionPolicy;
use Contao\TestCase\ContaoTestCase;

class RetentionPolicyTest extends ContaoTestCase
{
    /**
     * @dataProvider invalidIntervalFormatProvider
     */
    public function testThrowsOnInvalidIntervalFormat(array $intervals): void
    {
        $this->expectException(\Exception::class);

        new RetentionPolicy(0, $intervals);
    }

    public function invalidIntervalFormatProvider(): \Generator
    {
        yield [['foobar']];
        yield [[1]];
        yield [['P1D']];
    }

    /**
     * @dataProvider policyProvider
     *
     * @param array<int>    $keepIntervals
     * @param array<Backup> $allBackups
     * @param array<string> $expectedBackupFilePathsToKeep
     */
    public function testPolicy(int $keepMax, array $keepIntervals, Backup $latestBackup, array $allBackups, array $expectedBackupFilePathsToKeep): void
    {
        $retentionPolicy = new RetentionPolicy($keepMax, $keepIntervals);

        // Backups are to be passed on sorted according to the interface
        usort($allBackups, static fn (Backup $a, Backup $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        $toKeep = array_map(static fn (Backup $backup) => $backup->getFilename(), $retentionPolicy->apply($latestBackup, $allBackups));

        $this->assertSame($expectedBackupFilePathsToKeep, $toKeep);
    }

    public function policyProvider(): \Generator
    {
        yield 'Test should delete oldest when only keepMax is configured' => [
            5,
            [],
            $this->createBackup('2021-11-16T13:36:00+00:00'),
            [
                $this->createBackup('2021-11-16T13:36:00+00:00'),
                $this->createBackup('2021-11-15T13:36:00+00:00'),
                $this->createBackup('2021-11-14T13:36:00+00:00'),
                $this->createBackup('2021-11-13T13:36:00+00:00'),
                $this->createBackup('2021-11-12T13:36:00+00:00'),
                $this->createBackup('2021-11-11T13:36:00+00:00'),
            ],
            [
                'backup__20211116133600.sql.gz',
                'backup__20211115133600.sql.gz',
                'backup__20211114133600.sql.gz',
                'backup__20211113133600.sql.gz',
                'backup__20211112133600.sql.gz',
            ],
        ];

        yield 'Test keepMax configured to 0 does not clean up at all' => [
            0,
            [],
            $this->createBackup('2021-11-16T13:36:00+00:00'),
            [
                $this->createBackup('2021-11-16T13:36:00+00:00'),
                $this->createBackup('2021-11-15T13:36:00+00:00'),
                $this->createBackup('2021-11-14T13:36:00+00:00'),
                $this->createBackup('2021-11-13T13:36:00+00:00'),
                $this->createBackup('2021-11-12T13:36:00+00:00'),
            ],
            [
                'backup__20211116133600.sql.gz',
                'backup__20211115133600.sql.gz',
                'backup__20211114133600.sql.gz',
                'backup__20211113133600.sql.gz',
                'backup__20211112133600.sql.gz',
            ],
        ];

        yield 'Test keepMax configured to 0 and keepIntervals correctly keeps the correct backups' => [
            0,
            ['1D', '7D', '1M'], // Should keep the latest plus the oldest of the periods 1 day ago, 7 days ago, a month ago
            $this->createBackup('2021-11-16T13:36:00+00:00'),
            [
                $this->createBackup('2021-11-16T13:36:00+00:00'),
                $this->createBackup('2021-11-16T08:36:00+00:00'),
                $this->createBackup('2021-11-16T06:36:00+00:00'),
                $this->createBackup('2021-11-07T13:36:00+00:00'),
                $this->createBackup('2021-09-07T13:36:00+00:00'),
                $this->createBackup('2021-11-07T18:36:00+00:00'),
                $this->createBackup('2021-11-12T13:36:00+00:00'),
                $this->createBackup('2021-11-11T13:36:00+00:00'),
                $this->createBackup('2021-11-13T13:36:00+00:00'),
            ],
            [
                'backup__20211116133600.sql.gz', // This is the latest
                'backup__20211116063600.sql.gz', // This is the oldest for -1 day ago
                'backup__20211111133600.sql.gz', // This is the oldest for -7 days ago
                'backup__20211107133600.sql.gz', // This is the oldest for -30 days ago
            ],
        ];

        yield 'Test keepMax configured to 2 and keepIntervals correctly keeps the correct backups' => [
            2,
            ['1D', '7D', '1M'], // Should keep the latest plus the oldest of the periods 1 day ago, 7 days ago, a month ago
            $this->createBackup('2021-11-16T13:36:00+00:00'),
            [
                $this->createBackup('2021-11-16T13:36:00+00:00'),
                $this->createBackup('2021-11-16T08:36:00+00:00'),
                $this->createBackup('2021-11-16T06:36:00+00:00'),
                $this->createBackup('2021-11-07T13:36:00+00:00'),
                $this->createBackup('2021-09-07T13:36:00+00:00'),
                $this->createBackup('2021-11-07T18:36:00+00:00'),
                $this->createBackup('2021-11-12T13:36:00+00:00'),
                $this->createBackup('2021-11-11T13:36:00+00:00'),
                $this->createBackup('2021-11-13T13:36:00+00:00'),
            ],
            [
                'backup__20211116133600.sql.gz', // This is the latest
                'backup__20211116063600.sql.gz', // This is the oldest for -1 day ago
                // According to keepIntervals, we'd keep more backups, but we are limited by the total
            ],
        ];
    }

    private function createBackup(string $atomDateTime): Backup
    {
        $dt = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $atomDateTime);

        return new Backup(sprintf('backup__%s.sql.gz', $dt->format(Backup::DATETIME_FORMAT)));
    }
}
