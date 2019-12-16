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
use Contao\CoreBundle\Entity\Cron as CronEntity;
use Contao\CoreBundle\Repository\CronRepository;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CronTest extends TestCase
{
    public function testExecutesAddedCronJob(): void
    {
        $repository = $this->createMock(CronRepository::class);

        $cronjob = $this->getMockBuilder(\stdClass::class)->addMethods(['onHourly'])->getMock();
        $cronjob
            ->expects($this->once())
            ->method('onHourly')
        ;

        $cron = new Cron($repository);

        $cron->addCronJob($cronjob, 'onHourly', '@hourly');

        $cron->run();
    }

    public function testCronJobsAreExecutedInSpecifiedOrder(): void
    {
        $repository = $this->createMock(CronRepository::class);

        $cronjob = $this->getMockBuilder(\stdClass::class)->addMethods(['first', 'second', 'third'])->getMock();
        $cronjob->expects($this->at(0))->method('first')->with();
        $cronjob->expects($this->at(1))->method('second')->with();
        $cronjob->expects($this->at(2))->method('third')->with();

        $cron = new Cron($repository);

        $cron->addCronJob($cronjob, 'third', '@hourly', -10);
        $cron->addCronJob($cronjob, 'first', '@hourly', 10);
        $cron->addCronJob($cronjob, 'second', '@hourly');

        $cron->run();
    }

    public function testOnlyWebCronJobsAreNotExecutedIfScopeIsWeb(): void
    {
        $repository = $this->createMock(CronRepository::class);

        $cronjob = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName('TestWebCron')
            ->addMethods(['both', 'web', 'cli'])
            ->getMock()
        ;

        $cronjob
            ->expects($this->once())
            ->method('both')
        ;

        $cronjob
            ->expects($this->never())
            ->method('cli')
        ;

        $cronjob
            ->expects($this->once())
            ->method('web')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                ['Skipping cron job "TestWebCron::cli" for scope ['.Cron::SCOPE_WEB.']'],
                ['Executing cron job "TestWebCron::both"'],
                ['Executing cron job "TestWebCron::web"']
            )
        ;

        $cron = new Cron($repository, $logger);

        $cron->addCronJob($cronjob, 'both', '@hourly');
        $cron->addCronJob($cronjob, 'web', '@hourly', 0, Cron::SCOPE_WEB);
        $cron->addCronJob($cronjob, 'cli', '@hourly', 0, Cron::SCOPE_CLI);

        $cron->run([Cron::SCOPE_WEB]);
    }

    public function testOnlyCliCronJobsAreExecutedWhenScopeIsCli(): void
    {
        $repository = $this->createMock(CronRepository::class);

        $cronjob = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName('TestCliCron')
            ->addMethods(['both', 'web', 'cli'])
            ->getMock()
        ;

        $cronjob
            ->expects($this->once())
            ->method('both')
        ;

        $cronjob
            ->expects($this->once())
            ->method('cli')
        ;

        $cronjob
            ->expects($this->never())
            ->method('web')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                ['Skipping cron job "TestCliCron::web" for scope ['.Cron::SCOPE_CLI.']'],
                ['Executing cron job "TestCliCron::both"'],
                ['Executing cron job "TestCliCron::cli"']
            )
        ;

        $cron = new Cron($repository, $logger);

        $cron->addCronJob($cronjob, 'both', '@hourly');
        $cron->addCronJob($cronjob, 'cli', '@hourly', 0, Cron::SCOPE_CLI);
        $cron->addCronJob($cronjob, 'web', '@hourly', 0, Cron::SCOPE_WEB);

        $cron->run([Cron::SCOPE_CLI]);
    }

    public function testUpdatesCronEntities(): void
    {
        $repository = $this->createMock(CronRepository::class);

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
            ->with($this->callback(static function (CronEntity $entity) {
                return 'UpdateEntitiesCron::onHourly' === $entity->getName() && (new \DateTime()) >= $entity->getLastRun();
            }))
        ;

        $cron = new Cron($repository);

        $cron->addCronJob($cronjob, 'onHourly', '@hourly');

        $cron->run();
    }

    /**
     * @return CronEntity&MockObject
     */
    private function mockEntity(string $name, \DateTime $lastRun = null): CronEntity
    {
        $entity = $this->createMock(CronEntity::class);

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
