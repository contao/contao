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

use Contao\CoreBundle\Cron\LegacyCron;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;

class LegacyCronTest extends TestCase
{
    public function testLegacyCronJobsAreExecuted(): void
    {
        // Mock a simple object to be used for TL_CRON
        $legacyCronObject = $this->getMockBuilder(\stdClass::class)->setMethods(['onMinutely', 'onHourly', 'onDaily', 'onWeekly', 'onMonthly'])->getMock();
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

        // Mock the System adapter and return the simple object
        $systemAdapter = $this->mockAdapter(['importStatic', 'loadLanguageFile']);
        $systemAdapter
            ->expects($this->exactly(5))
            ->method('importStatic')
            ->with('TestCron')
            ->willReturn($legacyCronObject)
        ;

        // Mock the Contao framework with the System adapter
        $framework = $this->mockContaoFramework([
            System::class => $systemAdapter,
        ]);

        // Create a LegacyCron instance and execute all interval functions
        $legacyCron = new LegacyCron($framework);
        $legacyCron->onMinutely();
        $legacyCron->onHourly();
        $legacyCron->onDaily();
        $legacyCron->onWeekly();
        $legacyCron->onMonthly();

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
}
