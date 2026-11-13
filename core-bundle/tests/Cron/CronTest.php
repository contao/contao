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
use Contao\CoreBundle\Cron\CronJob;
use Contao\CoreBundle\Entity\CronJob as CronJobEntity;
use Contao\CoreBundle\Fixtures\Cron\TestCronJob;
use Contao\CoreBundle\Fixtures\Cron\TestInvokableCronJob;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CronTest extends TestCase
{
    public function testExecutesAddedCronJob(): void
    {
        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $cron = new Cron(
            fn () => $this->createConfiguredStub(CronJobRepository::class, ['createJob' => 1, 'updateLastRun' => true]),
            fn () => $this->createEntityManagerMock(false),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testExecutesSingleCronJob(): void
    {
        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $cron = new Cron(
            fn () => $this->createConfiguredStub(CronJobRepository::class, ['createJob' => 1, 'updateLastRun' => true]),
            fn () => $this->createEntityManagerMock(false),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->runJob($cronjob::class.'::onHourly', Cron::SCOPE_CLI);
    }

    public function testLoggingOfExecutedCronJobs(): void
    {
        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->once())
            ->method('onMinutely')
        ;

        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $expectedMessages = [
            'Executing cron job "'.$cronjob::class.'::onMinutely"',
            'Executing cron job "'.$cronjob::class.'::onHourly"',
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('debug')
            ->with($this->callback(
                static function (string $message) use (&$expectedMessages) {
                    $pos = array_search($message, $expectedMessages, true);
                    unset($expectedMessages[$pos]);

                    return false !== $pos;
                },
            ))
        ;

        $cron = new Cron(
            fn () => $this->createConfiguredStub(CronJobRepository::class, ['createJob' => 1, 'updateLastRun' => true]),
            fn () => $this->createEntityManagerMock(false),
            $this->createStub(CacheItemPoolInterface::class),
            $logger,
        );

        $cron->addCronJob(new CronJob($cronjob, '* * * * *', 'onMinutely'));
        $cron->addCronJob(new CronJob($cronjob, '0 * * * *', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testUpdatesCronEntities(): void
    {
        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $entity
            ->method('getName')
            ->willReturn('UpdateEntitiesCron::onHourly')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn(new \DateTime()->modify('-1 hours'))
        ;

        $entity
            ->method('getId')
            ->willReturn(1)
        ;

        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('findOneByName')
            ->with($cronjob::class.'::onHourly')
            ->willReturn($entity)
        ;

        $repository
            ->expects($this->once())
            ->method('purgeOldRecords')
            ->with([$cronjob::class.'::onHourly'])
        ;

        $manager = $this->createEntityManagerMock(false);
        $manager
            ->expects($this->never())
            ->method('flush')
        ;

        $cron = new Cron(
            static fn () => $repository,
            static fn () => $manager,
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testSetsScope(): void
    {
        $cronjob = $this->createMock(TestInvokableCronJob::class);
        $cronjob
            ->expects($this->once())
            ->method('__invoke')
            ->with(Cron::SCOPE_CLI)
        ;

        $cron = new Cron(
            fn () => $this->createConfiguredStub(CronJobRepository::class, ['createJob' => 1, 'updateLastRun' => true]),
            fn () => $this->createEntityManagerMock(false),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testInvalidArgumentExceptionForScope(): void
    {
        $cron = new Cron(
            fn () => $this->createConfiguredStub(CronJobRepository::class, ['createJob' => 1, 'updateLastRun' => true]),
            fn () => $this->createEntityManagerMock(false),
            $this->createStub(CacheItemPoolInterface::class),
        );

        try {
            $cron->run(Cron::SCOPE_CLI);
            $cron->run(Cron::SCOPE_WEB);
        } catch (\InvalidArgumentException) {
            $this->fail();
        }

        $this->expectException(\InvalidArgumentException::class);
        $cron->run('invalid_scope');
    }

    public function testDoesNotInstantiateDependenciesInConstructor(): void
    {
        $initialized = false;

        $cron = new Cron(
            function () use (&$initialized): CronJobRepository {
                $initialized = true;

                return $this->createStub(CronJobRepository::class);
            },
            function () use (&$initialized): EntityManagerInterface {
                $initialized = true;

                return $this->createEntityManagerMock(false);
            },
            $this->createStub(CacheItemPoolInterface::class),
        );

        $this->assertEmpty($cron->getCronJobs());
        $this->assertFalse($initialized);
    }

    public function testDoesNotRunCronJobIfAlreadyRun(): void
    {
        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->method('getName')
            ->willReturn('UpdateEntitiesCron::onHourly')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn(new \DateTime()->modify('+2 hours'))
        ;

        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $entity
            ->method('getId')
            ->willReturn(1)
        ;

        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->never())
            ->method('onHourly')
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('findOneByName')
            ->with($cronjob::class.'::onHourly')
            ->willReturn($entity)
        ;

        $repository
            ->expects($this->once())
            ->method('purgeOldRecords')
            ->with([$cronjob::class.'::onHourly'])
        ;

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createEntityManagerMock(false),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testForcesCronJobToBeRunIfAlreadyRun(): void
    {
        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->method('getName')
            ->willReturn('UpdateEntitiesCron::onHourly')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn(new \DateTime()->modify('+2 hours'))
        ;

        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $entity
            ->method('getId')
            ->willReturn(1)
        ;

        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('findOneByName')
            ->with($cronjob::class.'::onHourly')
            ->willReturn($entity)
        ;

        $repository
            ->expects($this->once())
            ->method('purgeOldRecords')
            ->with([$cronjob::class.'::onHourly'])
        ;

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createEntityManagerMock(false),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI, true);
    }

    public function testMinutelyCronJob(): void
    {
        $lastRun = new \DateTime()->modify('-1 hours');

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $entity
            ->method('getName')
            ->willReturn('Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($lastRun)
        ;

        $entity
            ->method('getId')
            ->willReturn(1)
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('findOneByName')
            ->with('Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron')
            ->willReturn($entity)
        ;

        $repository
            ->expects($this->once())
            ->method('purgeOldRecords')
            ->with(['Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron'])
        ;

        $cache = new ArrayAdapter();

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createEntityManagerMock(true),
            $cache,
            $this->createStub(LoggerInterface::class),
        );

        $cron->addCronJob(new CronJob($cron, '* * * * *', 'updateMinutelyCliCron'));
        $this->assertFalse($cron->hasMinutelyCliCron());

        $cron->run(Cron::SCOPE_CLI);
        $this->assertTrue($cron->hasMinutelyCliCron());

        $cache->clear();
        $this->assertFalse($cron->hasMinutelyCliCron());
    }

    public function testMinutelyCronJobResetsLastRunInWebScope(): void
    {
        $lastRun = new \DateTime()->modify('-1 hours');

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $entity
            ->method('getName')
            ->willReturn('Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($lastRun)
        ;

        $entity
            ->method('getId')
            ->willReturn(1)
        ;

        $repository = $this->createRepositoryMock();
        $claimed = null;
        $matcher = $this->exactly(2);
        $repository
            ->expects($matcher)
            ->method('updateLastRun')
            ->with(
                1,
                $this->callback(
                    static function (\DateTimeInterface $date) use ($matcher, &$claimed, $lastRun): bool {
                        if (1 === $matcher->numberOfInvocations()) {
                            $claimed = $date;

                            return true;
                        }

                        return $date === $lastRun;
                    },
                ),
                $this->callback(
                    static function (\DateTimeInterface $date) use ($matcher, &$claimed, $lastRun): bool {
                        return $date === (1 === $matcher->numberOfInvocations() ? $lastRun : $claimed);
                    },
                ),
            )
        ;
        $repository
            ->expects($this->once())
            ->method('findOneByName')
            ->with('Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron')
            ->willReturn($entity)
        ;

        $repository
            ->expects($this->once())
            ->method('purgeOldRecords')
            ->with(['Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron'])
        ;

        $cache = new ArrayAdapter();

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createEntityManagerMock(false),
            $cache,
        );

        $cron->addCronJob(new CronJob($cron, '* * * * *', 'updateMinutelyCliCron'));
        $cron->run(Cron::SCOPE_WEB);
    }

    public function testResetsLastRunForSkippedCronJobs(): void
    {
        $previousRun = new \DateTime()->modify('-1 hours');

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $entity
            ->method('getName')
            ->willReturn('Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingMethod')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($previousRun)
        ;

        $entity
            ->method('getId')
            ->willReturn(1)
        ;

        $repository = $this->createRepositoryMock();
        $claimed = null;
        $matcher = $this->exactly(2);
        $repository
            ->expects($matcher)
            ->method('updateLastRun')
            ->with(
                1,
                $this->callback(
                    static function (\DateTimeInterface $date) use ($matcher, &$claimed, $previousRun): bool {
                        if (1 === $matcher->numberOfInvocations()) {
                            $claimed = $date;

                            return true;
                        }

                        return $date === $previousRun;
                    },
                ),
                $this->callback(
                    static function (\DateTimeInterface $date) use ($matcher, &$claimed, $previousRun): bool {
                        return $date === (1 === $matcher->numberOfInvocations() ? $previousRun : $claimed);
                    },
                ),
            )
        ;
        $repository
            ->expects($this->once())
            ->method('findOneByName')
            ->with('Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingMethod')
            ->willReturn($entity)
        ;

        $repository
            ->expects($this->once())
            ->method('purgeOldRecords')
            ->with(['Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingMethod'])
        ;

        $cronjob = new TestCronJob();

        $manager = $this->createEntityManagerMock(false);
        $manager
            ->expects($this->never())
            ->method('flush')
        ;

        $cron = new Cron(
            static fn () => $repository,
            static fn () => $manager,
            new ArrayAdapter(),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'skippingMethod'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testResetsLastRunForSkippedAsyncCronJobs(): void
    {
        $previousRun = new \DateTime()->modify('-1 hours');

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $entity
            ->method('getName')
            ->willReturn('Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingAsyncMethod')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($previousRun)
        ;

        $entity
            ->method('getId')
            ->willReturn(1)
        ;

        $repository = $this->createRepositoryMock();
        $claimed = null;
        $matcher = $this->exactly(2);
        $repository
            ->expects($matcher)
            ->method('updateLastRun')
            ->with(
                1,
                $this->callback(
                    static function (\DateTimeInterface $date) use ($matcher, &$claimed, $previousRun): bool {
                        if (1 === $matcher->numberOfInvocations()) {
                            $claimed = $date;

                            return true;
                        }

                        return $date === $previousRun;
                    },
                ),
                $this->callback(
                    static function (\DateTimeInterface $date) use ($matcher, &$claimed, $previousRun): bool {
                        return $date === (1 === $matcher->numberOfInvocations() ? $previousRun : $claimed);
                    },
                ),
            )
        ;
        $repository
            ->expects($this->once())
            ->method('findOneByName')
            ->with('Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingAsyncMethod')
            ->willReturn($entity)
        ;

        $repository
            ->expects($this->once())
            ->method('purgeOldRecords')
            ->with(['Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingAsyncMethod'])
        ;

        $cronjob = new TestCronJob();

        $manager = $this->createEntityManagerMock(true);
        $manager
            ->expects($this->never())
            ->method('flush')
        ;

        $cron = new Cron(
            static fn () => $repository,
            static fn () => $manager,
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'skippingAsyncMethod'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testSkipsIfClaimIsLost(): void
    {
        $repository = $this->createRepositoryMock(false);
        $repository
            ->method('findOneByName')
            ->willReturn($this->createCronJobEntity(1, new \DateTimeImmutable('-1 hour')))
        ;

        $repository
            ->expects($this->once())
            ->method('updateLastRun')
        ;

        $service = $this->createMock(TestCronJob::class);
        $service
            ->expects($this->never())
            ->method('onHourly')
        ;
        $cron = new Cron(static fn () => $repository, fn () => $this->createEntityManagerMock(false), new ArrayAdapter());
        $cron->addCronJob(new CronJob($service, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testForcesExecutionEvenIfUpdateAffectsNoRows(): void
    {
        $repository = $this->createRepositoryMock(false);
        $repository
            ->method('findOneByName')
            ->willReturn($this->createCronJobEntity(1, new \DateTimeImmutable()))
        ;

        $repository
            ->expects($this->once())
            ->method('updateLastRun')
            ->with(1, $this->isInstanceOf(\DateTimeInterface::class), null)
        ;

        $service = $this->createMock(TestCronJob::class);
        $service
            ->expects($this->once())
            ->method('onHourly')
        ;
        $cron = new Cron(static fn () => $repository, fn () => $this->createEntityManagerMock(false), new ArrayAdapter());
        $cron->addCronJob(new CronJob($service, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI, true);
    }

    public function testSkipsIfAnotherProcessCreatesTheJob(): void
    {
        $repository = $this->createRepositoryMock(true, null);
        $repository
            ->expects($this->once())
            ->method('createJob')
        ;

        $repository
            ->expects($this->never())
            ->method('updateLastRun')
        ;

        $service = $this->createMock(TestCronJob::class);
        $service
            ->expects($this->never())
            ->method('onHourly')
        ;
        $cron = new Cron(static fn () => $repository, fn () => $this->createEntityManagerMock(false), new ArrayAdapter());
        $cron->addCronJob(new CronJob($service, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testRunsSingleJobByItsOriginalUnicodeName(): void
    {
        $this->expectUserDeprecationMessage('Since contao/core-bundle 5.3: Using non-ASCII characters in cron job names is deprecated and will no longer work in Contao 7.');
        $service = $this->createMock(TestInvokableCronJob::class);
        $service
            ->expects($this->once())
            ->method('__invoke')
        ;
        $cron = new Cron(fn () => $this->createConfiguredStub(CronJobRepository::class, ['createJob' => 1, 'updateLastRun' => true]), fn () => $this->createEntityManagerMock(false), new ArrayAdapter());
        $cron->addCronJob(new CronJob($service, '@hourly', name: 'täglich'));
        $this->expectUserDeprecationMessage('Since contao/core-bundle 5.3: Using non-ASCII characters in cron job names is deprecated and will no longer work in Contao 7.');
        $cron->runJob('täglich', Cron::SCOPE_CLI);
    }

    public function testForcesExecutionAfterAnInitializationConflict(): void
    {
        $repository = $this->createRepositoryMock(false, null);
        $repository
            ->expects($this->exactly(2))
            ->method('findOneByName')
            ->willReturnOnConsecutiveCalls(null, $this->createCronJobEntity(42, new \DateTimeImmutable()))
        ;

        $repository
            ->expects($this->once())
            ->method('updateLastRun')
            ->with(42, $this->isInstanceOf(\DateTimeInterface::class), null)
        ;
        $service = $this->createMock(TestInvokableCronJob::class);
        $service
            ->expects($this->once())
            ->method('__invoke')
        ;
        $cron = new Cron(static fn () => $repository, fn () => $this->createEntityManagerMock(false), new ArrayAdapter());
        $cron->addCronJob(new CronJob($service, '@hourly'));
        $cron->run(Cron::SCOPE_CLI, true);
    }

    public function testThrowsIfJobIsMissingAfterAnInitializationConflict(): void
    {
        $repository = $this->createRepositoryMock(false, null);
        $repository
            ->expects($this->exactly(2))
            ->method('findOneByName')
            ->willReturn(null)
        ;

        $repository
            ->expects($this->once())
            ->method('createJob')
        ;

        $service = $this->createMock(TestInvokableCronJob::class);
        $service
            ->expects($this->never())
            ->method('__invoke')
        ;
        $cron = new Cron(static fn () => $repository, fn () => $this->createEntityManagerMock(false), new ArrayAdapter());
        $cron->addCronJob(new CronJob($service, '@hourly', name: 'missing_job'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cron job "missing_job" does not exist after a unique constraint violation.');

        $cron->run(Cron::SCOPE_CLI, true);
    }

    private function createCronJobEntity(int $id, \DateTimeInterface $lastRun): CronJobEntity
    {
        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->method('getId')
            ->willReturn($id)
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($lastRun)
        ;

        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        return $entity;
    }

    private function createRepositoryMock(bool $updated = true, int|null $id = 1): CronJobRepository&MockObject
    {
        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->method('createJob')
            ->willReturn($id)
        ;

        $repository
            ->method('updateLastRun')
            ->willReturn($updated)
        ;

        return $repository;
    }

    private function createEntityManagerMock(bool $expectsClose): EntityManagerInterface&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($expectsClose ? $this->once() : $this->never())
            ->method('close')
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($expectsClose ? $this->once() : $this->never())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $connection
            ->expects($this->never())
            ->method('beginTransaction')
        ;

        $connection
            ->expects($this->never())
            ->method('commit')
        ;

        $connection
            ->expects($this->never())
            ->method('rollBack')
        ;

        return $entityManager;
    }
}
