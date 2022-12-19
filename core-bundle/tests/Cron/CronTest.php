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
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Promise\Promise;
use Psr\Log\LoggerInterface;

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
            fn () => $this->createMock(CronJobRepository::class),
            fn () => $this->createMock(EntityManagerInterface::class)
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
            fn () => $this->createMock(CronJobRepository::class),
            fn () => $this->createMock(EntityManagerInterface::class)
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

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                ['Executing cron job "TestCronJob::onMinutely"'],
                ['Executing cron job "TestCronJob::onHourly"']
            )
        ;

        $cron = new Cron(
            fn () => $this->createMock(CronJobRepository::class),
            fn () => $this->createMock(EntityManagerInterface::class),
            $logger
        );

        $cron->addCronJob(new CronJob($cronjob, '* * * * *', 'onMinutely'));
        $cron->addCronJob(new CronJob($cronjob, '0 * * * *', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testRunsAsyncCrons(): void
    {
        $promise1 = new Promise(static function () use (&$promise1): void { $promise1->resolve('Success'); });
        $promise2 = new Promise(static function () use (&$promise2): void { $promise2->reject('Failure'); });

        $cronjob1 = $this->getMockBuilder(TestCronJob::class)->setMockClassName('TestCronJob')->getMock();
        $cronjob1
            ->expects($this->once())
            ->method('asyncMethod')
            ->willReturn($promise1)
        ;

        $cronjob2 = $this->getMockBuilder(TestCronJob::class)->setMockClassName('TestCronJob2')->getMock();
        $cronjob2
            ->expects($this->once())
            ->method('asyncMethod')
            ->willReturn($promise2)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(4))
            ->method('debug')
            ->withConsecutive(
                ['Executing cron job "TestCronJob::asyncMethod"'],
                ['Executing cron job "TestCronJob2::asyncMethod"'],
                ['Asynchronous cron job "TestCronJob::asyncMethod" finished successfully'],
                ['Asynchronous cron job "TestCronJob2::asyncMethod" failed: Failure'],
            )
        ;

        $cron = new Cron(
            fn () => $this->createMock(CronJobRepository::class),
            fn () => $this->createMock(EntityManagerInterface::class),
            $logger
        );

        $cron->addCronJob(new CronJob($cronjob1, '* * * * *', 'asyncMethod'));
        $cron->addCronJob(new CronJob($cronjob2, '* * * * *', 'asyncMethod'));
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
            ->with($this->equalTo('findOneByName'), $this->equalTo(['UpdateEntitiesCron::onHourly']))
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

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('flush')
        ;

        $cron = new Cron(static fn () => $repository, static fn () => $manager);
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
            fn () => $this->createMock(CronJobRepository::class),
            fn () => $this->createMock(EntityManagerInterface::class)
        );

        $cron->addCronJob(new CronJob($cronjob, '@hourly'));
        $cron->run(Cron::SCOPE_CLI);
    }

    public function testInvalidArgumentExceptionForScope(): void
    {
        $cron = new Cron(
            fn () => $this->createMock(CronJobRepository::class),
            fn () => $this->createMock(EntityManagerInterface::class)
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
        $cron = new Cron(
            static function (): never {
                throw new \LogicException();
            },
            static function (): never {
                throw new \LogicException();
            }
        );

        $this->expectException(\LogicException::class);

        $cron->run(Cron::SCOPE_CLI);
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

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('findOneByName'), $this->equalTo(['UpdateEntitiesCron::onHourly']))
            ->willReturn($entity)
        ;

        $cronjob = $this
            ->getMockBuilder(TestCronJob::class)
            ->setMockClassName('UpdateEntitiesCron')
            ->getMock()
        ;

        $cronjob
            ->expects($this->never())
            ->method('onHourly')
        ;

        $cron = new Cron(static fn () => $repository, fn () => $this->createMock(EntityManagerInterface::class));
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
            ->willReturn((new \DateTime())->modify('-1 minute'))
        ;

        $entity
            ->expects($this->once())
            ->method('setLastRun')
        ;

        $repository = $this->createMock(CronJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('findOneByName'), $this->equalTo(['UpdateEntitiesCron::onHourly']))
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

        $cron = new Cron(static fn () => $repository, fn () => $this->createMock(EntityManagerInterface::class));
        $cron->addCronJob(new CronJob($cronjob, '@hourly', 'onHourly'));
        $cron->run(Cron::SCOPE_CLI, true);
    }
}
