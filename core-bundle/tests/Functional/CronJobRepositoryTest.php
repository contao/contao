<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\CoreBundle\Entity\CronJob;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class CronJobRepositoryTest extends FunctionalTestCase
{
    public function testKeepsLockDuringFlush(): void
    {
        self::bootKernel();
        self::resetDatabaseSchema();

        $doctrine = self::getContainer()->get('doctrine');
        $manager = $doctrine->getManager();
        $repository = new CronJobRepository($doctrine);
        $connection = DriverManager::getConnection($doctrine->getConnection()->getParams());

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $registry
            ->method('getManagerForClass')
            ->willReturn($manager)
        ;

        $otherRepository = new CronJobRepository($registry);

        $manager->persist(new CronJob('test'));
        $repository->lockTable();

        try {
            $manager->flush();

            $this->expectException(LockWaitTimeoutException::class);
            $otherRepository->lockTable();
        } finally {
            $this->assertFalse($connection->isTransactionActive());
            $this->assertSame(1, (int) $connection->fetchOne('SELECT @@autocommit'));
            $otherRepository->unlockTable();
            $connection->close();
            $repository->unlockTable();
        }
    }

    /**
     * @dataProvider provideTransactions
     */
    public function testBlocksIndependentWritersAndRestoresSession(bool $commit, int $autoCommit): void
    {
        self::bootKernel();
        self::resetDatabaseSchema();

        $doctrine = self::getContainer()->get('doctrine');
        $manager = $doctrine->getManager();
        $connection = $manager->getConnection();
        $otherConnection = DriverManager::getConnection($connection->getParams());
        $repository = new CronJobRepository($doctrine);

        $manager->persist($job = new CronJob('test', new \DateTimeImmutable('2000-01-01')));
        $manager->flush();
        $connection->executeStatement('SET SESSION autocommit = ?', [$autoCommit], [Types::INTEGER]);
        $otherConnection->executeStatement('SET SESSION lock_wait_timeout = 1');

        try {
            $repository->lockTable();
            $job->setLastRun(new \DateTimeImmutable('2100-01-01'));
            $manager->flush();

            try {
                $otherConnection->insert('tl_cron_job', ['name' => 'independent', 'lastRun' => '2000-01-01 00:00:00']);
                $this->fail('The table lock must block independent writers during flush.');
            } catch (LockWaitTimeoutException) {
                // Expected: this writer does not participate in cron locking.
            }

            $repository->unlockTable($commit);
            $this->assertFalse($connection->isTransactionActive());
            $this->assertSame($autoCommit, (int) $connection->fetchOne('SELECT @@autocommit'));
            $this->assertSame($commit ? '2100-01-01 00:00:00' : '2000-01-01 00:00:00', $otherConnection->fetchOne('SELECT lastRun FROM tl_cron_job WHERE name = ?', ['test']));
            $this->assertSame(1, $otherConnection->insert('tl_cron_job', ['name' => 'independent', 'lastRun' => '2000-01-01 00:00:00']));
        } finally {
            $repository->unlockTable(false);
            $otherConnection->close();
            $connection->executeStatement('SET SESSION autocommit = 1');
        }
    }

    public static function provideTransactions(): iterable
    {
        yield 'commit with autocommit enabled' => [true, 1];
        yield 'rollback with autocommit enabled' => [false, 1];
        yield 'commit with autocommit disabled' => [true, 0];
        yield 'rollback with autocommit disabled' => [false, 0];
    }
}
