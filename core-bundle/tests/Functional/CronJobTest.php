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
use Contao\CoreBundle\Migration\Version503\CronJobMigration;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class CronJobTest extends FunctionalTestCase
{
    private Connection $connection;

    private CronJobRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        self::getContainer()->get('request_stack')->push(Request::create('/'));
        $registry = self::getContainer()->get('doctrine');
        $this->connection = $registry->getConnection();
        $this->repository = new CronJobRepository($registry);
        $this->connection->executeStatement('DROP TABLE IF EXISTS tl_cron_job');
        $manager = $registry->getManager();
        $table = (new SchemaTool($manager))->getSchemaFromMetadata([$manager->getClassMetadata(CronJob::class)])->getTable('tl_cron_job');
        $this->connection->createSchemaManager()->createTable($table);
    }

    public function testOnlyOneConnectionCanClaimTheSameSnapshot(): void
    {
        $other = DriverManager::getConnection($this->connection->getParams());
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getConnection')
            ->willReturn($other)
        ;
        $manager = new EntityManager($other, self::getContainer()->get('doctrine')->getManager()->getConfiguration());
        $registry
            ->method('getManagerForClass')
            ->with(CronJob::class)
            ->willReturn($manager)
        ;
        $competitor = new CronJobRepository($registry);
        $previous = new \DateTimeImmutable('2026-01-01 12:00:00');
        $now = $previous->modify('+1 minute');

        try {
            $this->assertNull($this->repository->findOneByName('job'));
            $this->assertNull($competitor->findOneByName('job'));
            $id = $this->repository->createJob('job', $previous);
            $this->assertNotNull($id);
            $this->assertNull($competitor->createJob('job', $previous));
            $this->assertTrue($manager->isOpen());

            $first = $this->repository->findOneByName('job');
            $second = $competitor->findOneByName('job');
            $this->assertTrue($this->repository->updateLastRun($id, $now, $first->getLastRun()));
            $this->assertFalse($competitor->updateLastRun($id, $now, $second->getLastRun()));

            $later = $now->modify('+1 minute');
            $this->assertTrue($competitor->updateLastRun($id, $later, $now));
            $this->assertFalse($this->repository->updateLastRun($id, $previous, $now));
            $this->assertSame($first, $this->repository->findOneByName('job'));
            $this->assertSame($later->format('Y-m-d H:i:s'), $first->getLastRun()->format('Y-m-d H:i:s'));
            $unitOfWork = self::getContainer()->get('doctrine')->getManager()->getUnitOfWork();
            $unitOfWork->computeChangeSets();
            $this->assertSame([], $unitOfWork->getScheduledEntityUpdates());
            $this->assertTrue($competitor->updateLastRun($id, $now, $later));
            $this->assertSame($now->format('Y-m-d H:i:s'), $this->repository->findOneByName('job')->getLastRun()->format('Y-m-d H:i:s'));
            $this->assertFalse($this->connection->isTransactionActive());
            $this->assertFalse($other->isTransactionActive());
        } finally {
            $other->close();
        }
    }

    public function testMigrationNormalizesNamesAndKeepsTheNewestDuplicate(): void
    {
        $this->createLegacyTable();

        foreach ([
            [1, 'job', '2026-01-01 12:00:00'],
            [2, 'job', '2026-01-01 12:01:00'],
            [3, 'job', '2026-01-01 12:01:00'],
            [4, 'Job', '2026-01-01 12:00:00'],
            [5, 'täglich', '2026-01-01 12:00:00'],
            [6, 'job ', '2026-01-01 12:00:00'],
        ] as [$id, $name, $lastRun]) {
            $this->connection->insert('tl_cron_job', ['id' => $id, 'name' => $name, 'lastRun' => $lastRun]);
        }

        $migration = new CronJobMigration($this->connection);
        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());
        $this->assertFalse($migration->shouldRun());
        $this->assertSame([3, 4, 5], array_map(intval(...), $this->connection->fetchFirstColumn('SELECT id FROM tl_cron_job ORDER BY id')));
        $this->assertSame('tc3a4glich', $this->connection->fetchOne('SELECT name FROM tl_cron_job WHERE id = 5'));
        $this->assertNull($this->repository->createJob('job', new \DateTimeImmutable()));

        // COMPACT enforces the older 767-byte index limit even on current MySQL.
        $this->connection->executeStatement('ALTER TABLE tl_cron_job ROW_FORMAT=COMPACT');
        $this->assertNotNull($this->repository->createJob(str_repeat('a', 255), new \DateTimeImmutable()));
        $index = $this->connection->createSchemaManager()->listTableIndexes('tl_cron_job')['name'];
        $this->assertTrue($index->isUnique());
        $this->assertSame(['name'], $index->getColumns());
        $this->assertFalse($index->hasOption('lengths') && array_filter($index->getOption('lengths')));
    }

    public function testMigrationRejectsExpandedNamesBeforeChangingTheTable(): void
    {
        $this->createLegacyTable();
        $name = str_repeat('ä', 100);
        $this->connection->insert('tl_cron_job', ['name' => $name, 'lastRun' => '2026-01-01 12:00:00']);
        $migration = new CronJobMigration($this->connection);
        $this->assertFalse($migration->run()->isSuccessful());
        $this->assertSame($name, $this->connection->fetchOne('SELECT name FROM tl_cron_job'));
        $this->assertFalse($this->connection->createSchemaManager()->listTableIndexes('tl_cron_job')['name']->isUnique());
    }

    public function testMigrationRequiresTheLegacySchema(): void
    {
        $migration = new CronJobMigration($this->connection);
        $this->assertFalse($migration->shouldRun());

        $this->createLegacyTable();
        $this->assertTrue($migration->shouldRun());

        $this->connection->executeStatement('ALTER TABLE tl_cron_job DROP INDEX name, ADD UNIQUE INDEX name (name)');
        $this->assertFalse($migration->shouldRun());

        $this->connection->executeStatement('ALTER TABLE tl_cron_job DROP INDEX name, ADD INDEX name (lastRun)');
        $this->assertFalse($migration->shouldRun());

        $this->connection->executeStatement('ALTER TABLE tl_cron_job DROP INDEX name');
        $this->assertFalse($migration->shouldRun());

        $this->connection->executeStatement('ALTER TABLE tl_cron_job MODIFY name VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, ADD INDEX name (name)');
        $this->assertFalse($migration->shouldRun());

        $this->connection->executeStatement('DROP TABLE tl_cron_job');
        $this->assertFalse($migration->shouldRun());

        $this->connection->executeStatement('CREATE TABLE tl_cron_job (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, lastRun DATETIME NOT NULL, UNIQUE INDEX name (name))');
    }

    private function createLegacyTable(): void
    {
        $this->connection->executeStatement('ALTER TABLE tl_cron_job DROP INDEX name, MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, ADD INDEX name (name), ROW_FORMAT=DYNAMIC');
    }
}
