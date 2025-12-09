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
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
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
            fn () => $this->createStub(CronJobRepository::class),
            fn () => $this->createStub(EntityManagerInterface::class),
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
            fn () => $this->createStub(CronJobRepository::class),
            fn () => $this->createStub(EntityManagerInterface::class),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->runJob($cronjob::class.'::onHourly', Cron::SCOPE_CLI);
    }

    public function testLoggingOfExecutedCronJobs(): void
    {
        $cronjob = $this
            ->getMockBuilder(TestCronJob::class)
            ->setMockClassName('TestCronJob')
            ->getMock()
        ;

        $cronjob
            ->expects($this->once())
            ->method('onMinutely')
        ;

        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $expectedMessages = [
            'Executing cron job "TestCronJob::onMinutely"',
            'Executing cron job "TestCronJob::onHourly"',
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
            fn () => $this->createStub(CronJobRepository::class),
            fn () => $this->createStub(EntityManagerInterface::class),
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
            ->expects($this->once())
            ->method('setLastRun')
        ;

        $entity
            ->method('getName')
            ->willReturn('UpdateEntitiesCron::onHourly')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn((new \DateTime())->modify('-1 hours'))
        ;

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('__call')
            ->with('findOneByName', ['UpdateEntitiesCron::onHourly'])
            ->willReturn($entity)
        ;

        $cronjob = $this
            ->getMockBuilder(TestCronJob::class)
            ->setMockClassName('UpdateEntitiesCron')
            ->getMock()
        ;

        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $manager = $this->createEntityManagerMock(false);
        $manager
            ->expects($this->once())
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
            fn () => $this->createStub(CronJobRepository::class),
            fn () => $this->createStub(EntityManagerInterface::class),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testInvalidArgumentExceptionForScope(): void
    {
        $cron = new Cron(
            fn () => $this->createStub(CronJobRepository::class),
            fn () => $this->createStub(EntityManagerInterface::class),
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

                return $this->createStub(EntityManagerInterface::class);
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
            ->willReturn((new \DateTime())->modify('+2 hours'))
        ;

        $entity
            ->expects($this->never())
            ->method('setLastRun')
        ;

        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->never())
            ->method('onHourly')
        ;

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('__call')
            ->with('findOneByName', [$cronjob::class.'::onHourly'])
            ->willReturn($entity)
        ;

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createStub(EntityManagerInterface::class),
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
            ->willReturn((new \DateTime())->modify('+2 hours'))
        ;

        $entity
            ->expects($this->once())
            ->method('setLastRun')
        ;

        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('__call')
            ->with('findOneByName', [$cronjob::class.'::onHourly'])
            ->willReturn($entity)
        ;

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createStub(EntityManagerInterface::class),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI, true);
    }

    public function testMinutelyCronJob(): void
    {
        $lastRun = (new \DateTime())->modify('-1 hours');

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($this->once())
            ->method('setLastRun')
            ->willReturnSelf()
        ;

        $entity
            ->method('getName')
            ->willReturn('Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($lastRun)
        ;

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('__call')
            ->with('findOneByName', ['Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron'])
            ->willReturn($entity)
        ;

        $cache = new ArrayAdapter();

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createStub(EntityManagerInterface::class),
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
        $lastRun = (new \DateTime())->modify('-1 hours');

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($this->exactly(2))
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

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->exactly(2))
            ->method('__call')
            ->with($this->equalTo('findOneByName'), $this->equalTo(['Contao\CoreBundle\Cron\Cron::updateMinutelyCliCron']))
            ->willReturn($entity)
        ;

        $cache = new ArrayAdapter();

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createStub(EntityManagerInterface::class),
            $cache,
        );

        $cron->addCronJob(new CronJob($cron, '* * * * *', 'updateMinutelyCliCron'));
        $cron->run(Cron::SCOPE_WEB);
    }

    public function testResetsLastRunForSkippedCronJobs(): void
    {
        $previousRun = (new \DateTime())->modify('-1 hours');
        $matcher = $this->exactly(2);

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($matcher)
            ->method('setLastRun')
            ->with($this->callback(
                static function (\DateTimeInterface $lastRun) use ($matcher, $previousRun): bool {
                    if (2 === $matcher->numberOfInvocations()) {
                        return $lastRun === $previousRun;
                    }

                    return true;
                },
            ))
            ->willReturnSelf()
        ;

        $entity
            ->method('getName')
            ->willReturn('Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingMethod')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($previousRun)
        ;

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->exactly(2))
            ->method('__call')
            ->with('findOneByName', ['Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingMethod'])
            ->willReturn($entity)
        ;

        $cronjob = new TestCronJob();

        $manager = $this->createEntityManagerMock(false);
        $manager
            ->expects($this->exactly(2))
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
        $previousRun = (new \DateTime())->modify('-1 hours');
        $matcher = $this->exactly(2);

        $entity = $this->createMock(CronJobEntity::class);
        $entity
            ->expects($matcher)
            ->method('setLastRun')
            ->with($this->callback(
                static function (\DateTimeInterface $lastRun) use ($matcher, $previousRun): bool {
                    if (2 === $matcher->numberOfInvocations()) {
                        return $lastRun === $previousRun;
                    }

                    return true;
                },
            ))
            ->willReturnSelf()
        ;

        $entity
            ->method('getName')
            ->willReturn('Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingAsyncMethod')
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($previousRun)
        ;

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->exactly(2))
            ->method('__call')
            ->with('findOneByName', ['Contao\CoreBundle\Fixtures\Cron\TestCronJob::skippingAsyncMethod'])
            ->willReturn($entity)
        ;

        $cronjob = new TestCronJob();

        $manager = $this->createEntityManagerMock(true);
        $manager
            ->expects($this->exactly(2))
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

    public function testSkipsIfLockWaitTimeOut(): void
    {
        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('lockTable')
            ->willThrowException(new LockWaitTimeoutException($this->createStub(DriverException::class), null))
        ;

        $repository
            ->expects($this->never())
            ->method('__call')
        ;

        $cron = new Cron(
            static fn () => $repository,
            fn () => $this->createStub(EntityManagerInterface::class),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $cronjob = $this->createMock(TestCronJob::class);
        $cronjob
            ->expects($this->never())
            ->method('onHourly')
        ;

        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
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
            ->method('getConnection')
            ->willReturn($connection)
        ;

        return $entityManager;
    }
}
