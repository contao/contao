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
use Contao\CoreBundle\Cron\LegacyCron;
use Contao\CoreBundle\Fixtures\Cron\TestCronJob;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Doctrine\ORM\EntityManagerInterface;

class LegacyCronTest extends TestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation Using $GLOBALS['TL_CRON'] has been deprecated %s
     */
    public function testLegacyCronJobsAreExecuted(): void
    {
        // Mock a simple object to be used for TL_CRON
        $legacyCronObject = $this->createMock(TestCronJob::class);
        $legacyCronObject
            ->expects($this->once())
            ->method('onMinutely')
        ;

        $legacyCronObject
            ->expects($this->once())
            ->method('onHourly')
        ;

        $legacyCronObject
            ->expects($this->once())
            ->method('onDaily')
        ;

        $legacyCronObject
            ->expects($this->once())
            ->method('onWeekly')
        ;

        $legacyCronObject
            ->expects($this->once())
            ->method('onMonthly')
        ;

        // Register crons the legacy way
        $GLOBALS['TL_CRON'] = [
            'minutely' => [['TestCron', 'onMinutely']],
            'hourly' => [['TestCron', 'onHourly']],
            'daily' => [['TestCron', 'onDaily']],
            'weekly' => [['TestCron', 'onWeekly']],
            'monthly' => [['TestCron', 'onMonthly']],
        ];

        $systemAdapter = $this->mockAdapter(['importStatic', 'loadLanguageFile']);
        $systemAdapter
            ->expects($this->exactly(5))
            ->method('importStatic')
            ->with('TestCron')
            ->willReturn($legacyCronObject)
        ;

        // Mock the Contao framework with the System adapter
        $framework = $this->mockContaoFramework([System::class => $systemAdapter]);

        // Create a LegacyCron instance and add cron jobs to the cron service
        $legacyCron = new LegacyCron($framework);

        $cron = new Cron($this->fn($this->createMock(CronJobRepository::class)), $this->fn($this->createMock(EntityManagerInterface::class)));
        $cron->addCronJob(new CronJob($legacyCron, '* * * * *', 'onMinutely'));
        $cron->addCronJob(new CronJob($legacyCron, '@hourly', 'onHourly'));
        $cron->addCronJob(new CronJob($legacyCron, '@daily', 'onDaily'));
        $cron->addCronJob(new CronJob($legacyCron, '@weekly', 'onWeekly'));
        $cron->addCronJob(new CronJob($legacyCron, '@monthly', 'onMonthly'));
        $cron->run(Cron::SCOPE_CLI);

        unset($GLOBALS['TL_CRON']);
    }

    public function testSystemAdapterIsNotRetrievedWithoutCrons(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $legacyCron = new LegacyCron($framework);
        $legacyCron->onMinutely();
        $legacyCron->onHourly();
        $legacyCron->onDaily();
        $legacyCron->onWeekly();
        $legacyCron->onMonthly();
    }

    private function fn($service)
    {
        return static function () use ($service) {
            return $service;
        };
    }
}
