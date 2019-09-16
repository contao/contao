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
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CronTest extends TestCase
{
    public function testWillCreateCronDatabaseEntriesIfEmpty(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $connection
            ->expects($this->exactly(6))
            ->method('insert')
            ->withConsecutive(
                ['tl_cron', ['name' => 'lastrun', 'value' => $this->getTimestamp()]],
                ['tl_cron', ['name' => 'monthly', 'value' => 0]],
                ['tl_cron', ['name' => 'weekly', 'value' => 0]],
                ['tl_cron', ['name' => 'daily', 'value' => 0]],
                ['tl_cron', ['name' => 'hourly', 'value' => 0]],
                ['tl_cron', ['name' => 'minutely', 'value' => 0]]
            )
        ;

        $cron = new Cron($connection);
        $cron->run();
    }

    public function testUpdatesLastRunTimestampIfLastRunWasMoreThanOneDayAgo(): void
    {
        $result1 = $this->createMock(ResultStatement::class);
        $result1
            ->method('fetch')
            ->willReturn(['value' => (string) $this->getTimestamp(time() - 86400)])
        ;

        $result2 = $this->createMock(ResultStatement::class);
        $result2
            ->method('fetchAll')
            ->willReturn([])
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->withConsecutive(
                ["SELECT * FROM tl_cron WHERE name = 'lastrun'"],
                ["SELECT * FROM tl_cron WHERE name != 'lastrun'"]
            )
            ->willReturnOnConsecutiveCalls($result1, $result2)
        ;

        $connection
            ->expects($this->once())
            ->method('update')
            ->with('tl_cron', ['value' => $this->getTimestamp()], ['name' => 'lastrun'])
        ;

        $cron = new Cron($connection);
        $cron->run();
    }

    public function testDoesNothingIfLastRunWasLessThanSixtySecondsAgo(): void
    {
        $result = $this->createMock(ResultStatement::class);
        $result
            ->method('fetch')
            ->willReturn(['value' => (string) $this->getTimestamp()])
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with("SELECT * FROM tl_cron WHERE name = 'lastrun'")
            ->willReturn($result)
        ;

        $connection
            ->expects($this->never())
            ->method('insert')
        ;

        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $cron = new Cron($connection);
        $cron->run();
    }

    public function testExecutesAddedCronJob(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $cronjob = $this->getMockBuilder(\stdClass::class)->setMethods(['onMinutely'])->getMock();
        $cronjob
            ->expects($this->once())
            ->method('onMinutely')
        ;

        $cron = new Cron($connection);

        $cron->addCronJob($cronjob, 'onMinutely', 'minutely');

        $cron->run();
    }

    public function testCronUpdatesLastRunTimestamps(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $currentTimestamps = [
            'monthly' => date('Ym'),
            'weekly' => date('YW'),
            'daily' => date('Ymd'),
            'hourly' => date('YmdH'),
            'minutely' => date('YmdHi'),
        ];

        $connection
            ->expects($this->exactly(5))
            ->method('update')
            ->withConsecutive(
                ['tl_cron', ['value' => $currentTimestamps['monthly']], ['name' => 'monthly']],
                ['tl_cron', ['value' => $currentTimestamps['weekly']], ['name' => 'weekly']],
                ['tl_cron', ['value' => $currentTimestamps['daily']], ['name' => 'daily']],
                ['tl_cron', ['value' => $currentTimestamps['hourly']], ['name' => 'hourly']],
                ['tl_cron', ['value' => $currentTimestamps['minutely']], ['name' => 'minutely']]
            )
        ;

        $cronjob = $this->getMockBuilder(\stdClass::class)->setMethods(['test'])->getMock();

        $cron = new Cron($connection);

        $cron->addCronJob($cronjob, 'test', 'monthly');
        $cron->addCronJob($cronjob, 'test', 'weekly');
        $cron->addCronJob($cronjob, 'test', 'daily');
        $cron->addCronJob($cronjob, 'test', 'hourly');
        $cron->addCronJob($cronjob, 'test', 'minutely');

        $cron->run();
    }

    public function testCronJobsAreExecutedInSpecifiedOrder(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $cronjob = $this->getMockBuilder(\stdClass::class)->setMethods(['first', 'second', 'third'])->getMock();
        $cronjob->expects($this->at(0))->method('first')->with();
        $cronjob->expects($this->at(1))->method('second')->with();
        $cronjob->expects($this->at(2))->method('third')->with();

        $cron = new Cron($connection);

        $cron->addCronJob($cronjob, 'third', 'minutely', -10);
        $cron->addCronJob($cronjob, 'first', 'minutely', 10);
        $cron->addCronJob($cronjob, 'second', 'minutely');

        $cron->run();
    }

    public function testCliCronJobsAreNotExecutedIfScopeIsWeb(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $cronjob = $this->getMockBuilder(\stdClass::class)
            ->setMockClassName('TestCronJob')
            ->setMethods(['web', 'cli'])
            ->getMock()
        ;

        $cronjob
            ->expects($this->once())
            ->method('web')
        ;

        $cronjob
            ->expects($this->never())
            ->method('cli')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                ['Running the minutely cron jobs'],
                ['Skipping minutely cron job "TestCronJob::cli" for scope ['.Cron::SCOPE_WEB.']'],
                ['Minutely cron jobs complete']
            )
        ;

        $cron = new Cron($connection, $logger);

        $cron->addCronJob($cronjob, 'web', 'minutely');
        $cron->addCronJob($cronjob, 'cli', 'minutely', 0, Cron::SCOPE_CLI);

        $cron->run([Cron::SCOPE_WEB]);
    }

    public function testCliCronJobsAreExecutedWhenScopeIsCli(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $cronjob = $this->getMockBuilder(\stdClass::class)->setMethods(['web', 'cli'])->getMock();

        $cronjob
            ->expects($this->once())
            ->method('web')
        ;

        $cronjob
            ->expects($this->once())
            ->method('cli')
        ;

        $cron = new Cron($connection);

        $cron->addCronJob($cronjob, 'web', 'minutely');
        $cron->addCronJob($cronjob, 'cli', 'minutely', 0, Cron::SCOPE_CLI);

        $cron->run([Cron::SCOPE_CLI]);
    }

    public function testCronJobsAreNotRunWhenLastRunIsCurrent(): void
    {
        $currentTimestamps = [
            'monthly' => date('Ym'),
            'weekly' => date('YW'),
            'daily' => date('Ymd'),
            'hourly' => date('YmdH'),
            'minutely' => date('YmdHi'),
        ];

        $result1 = $this->createMock(ResultStatement::class);
        $result1
            ->method('fetch')
            ->willReturn(['value' => (string) $this->getTimestamp(time() - 60)])
        ;

        $result2 = $this->createMock(ResultStatement::class);
        $result2
            ->method('fetchAll')
            ->willReturn([
                ['name' => 'monthly', 'value' => $currentTimestamps['monthly']],
                ['name' => 'weekly', 'value' => $currentTimestamps['weekly']],
                ['name' => 'daily', 'value' => $currentTimestamps['daily']],
                ['name' => 'hourly', 'value' => $currentTimestamps['hourly']],
                ['name' => 'minutely', 'value' => $currentTimestamps['minutely']],
            ])
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->withConsecutive(
                ["SELECT * FROM tl_cron WHERE name = 'lastrun'"],
                ["SELECT * FROM tl_cron WHERE name != 'lastrun'"]
            )
            ->willReturnOnConsecutiveCalls($result1, $result2)
        ;

        $cronjob = $this->getMockBuilder(\stdClass::class)->setMethods(['test'])->getMock();

        $cronjob
            ->expects($this->never())
            ->method('test')
        ;

        $cron = new Cron($connection);

        $cron->addCronJob($cronjob, 'test', 'monthly');
        $cron->addCronJob($cronjob, 'test', 'weekly');
        $cron->addCronJob($cronjob, 'test', 'daily');
        $cron->addCronJob($cronjob, 'test', 'hourly');
        $cron->addCronJob($cronjob, 'test', 'minutely');

        $cron->run();
    }

    public function testLogsCriticalIfMethodDoesNotExist(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Cron job method "stdClass::test" does not exist!')
        ;

        $cron = new Cron($connection, $logger);

        $cron->addCronJob(new \stdClass(), 'test', 'minutely');

        $cron->run();
    }

    public function testLogsDebugInfoWhenRun(): void
    {
        $connection = $this->getEmptyDatabaseConnection();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(10))
            ->method('debug')
            ->withConsecutive(
                ['Running the monthly cron jobs'],
                ['Monthly cron jobs complete'],
                ['Running the weekly cron jobs'],
                ['Weekly cron jobs complete'],
                ['Running the daily cron jobs'],
                ['Daily cron jobs complete'],
                ['Running the hourly cron jobs'],
                ['Hourly cron jobs complete'],
                ['Running the minutely cron jobs'],
                ['Minutely cron jobs complete']
            )
        ;

        $cronjob = $this->getMockBuilder(\stdClass::class)->setMethods(['test'])->getMock();

        $cron = new Cron($connection, $logger);

        $cron->addCronJob($cronjob, 'test', 'minutely');
        $cron->addCronJob($cronjob, 'test', 'hourly');
        $cron->addCronJob($cronjob, 'test', 'daily');
        $cron->addCronJob($cronjob, 'test', 'weekly');
        $cron->addCronJob($cronjob, 'test', 'monthly');

        $cron->run();
    }

    /**
     * Returns a timestamp without seconds.
     */
    private function getTimestamp(int $timestamp = null): int
    {
        return strtotime(date('Y-m-d H:i', $timestamp ?? time()));
    }

    /**
     * Creates a database Connection mock where tl_cron is empty.
     *
     * @return Connection&MockObject
     */
    private function getEmptyDatabaseConnection(): Connection
    {
        $result1 = $this->createMock(ResultStatement::class);
        $result1
            ->method('fetch')
            ->willReturn(false)
        ;

        $result2 = $this->createMock(ResultStatement::class);
        $result2
            ->method('fetchAll')
            ->willReturn([])
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->withConsecutive(
                ["SELECT * FROM tl_cron WHERE name = 'lastrun'"],
                ["SELECT * FROM tl_cron WHERE name != 'lastrun'"]
            )
            ->willReturnOnConsecutiveCalls($result1, $result2)
        ;

        return $connection;
    }
}
