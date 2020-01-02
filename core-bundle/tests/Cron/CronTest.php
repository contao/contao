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
use Contao\CoreBundle\Entity\CronJob;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CronTest extends TestCase
{
    public function testExecutesAddedCronJob(): void
    {
        $repository = $this->createMock(CronJobRepository::class);

        $cronjob = $this->getMockBuilder(\stdClass::class)->addMethods(['onHourly'])->getMock();
        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $cron = new Cron($repository);

        $cron->addCronJob($cronjob, 'onHourly', '@hourly');

        $cron->run();
    }

    public function testLoggingOfExecutedCronJobs(): void
    {
        $repository = $this->createMock(CronJobRepository::class);

        $cronjob = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName('TestCronJob')
            ->addMethods(['onMinutely', 'onHourly'])
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

        $cron = new Cron($repository, $logger);

        $cron->addCronJob($cronjob, 'onMinutely', '* * * * *');
        $cron->addCronJob($cronjob, 'onHourly', '0 * * * *');

        $cron->run();
    }

    public function testUpdatesCronEntities(): void
    {
        $repository = $this->createMock(CronJobRepository::class);

        $entity = $this->mockEntity('UpdateEntitiesCron::onHourly', (new \DateTime())->modify('-1 hours'));

        $repository
            ->expects($this->once())
            ->method('__call')
            ->with(
                $this->equalTo('findOneByName'),
                $this->equalTo(['UpdateEntitiesCron::onHourly'])
            )
            ->willReturn($entity)
        ;

        $cronjob = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName('UpdateEntitiesCron')
            ->addMethods(['onHourly'])
            ->getMock()
        ;

        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $repository
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (CronJob $entity) {
                return 'UpdateEntitiesCron::onHourly' === $entity->getName() && (new \DateTime()) >= $entity->getLastRun();
            }))
        ;

        $cron = new Cron($repository);

        $cron->addCronJob($cronjob, 'onHourly', '@hourly');

        $cron->run();
    }

    /**
     * @return CronJob&MockObject
     */
    private function mockEntity(string $name, \DateTime $lastRun = null): CronJob
    {
        $entity = $this->createMock(CronJob::class);

        $entity
            ->method('getName')
            ->willReturn($name)
        ;

        $entity
            ->method('getLastRun')
            ->willReturn($lastRun ?? new \DateTime())
        ;

        return $entity;
    }
}
