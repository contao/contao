<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Contao\System;

class LegacyCron
{
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @CronJob("minutely")
     */
    public function onMinutely(): void
    {
        $this->runLegacyCrons('minutely');
    }

    /**
     * @CronJob("hourly")
     */
    public function onHourly(): void
    {
        $this->runLegacyCrons('hourly');
    }

    /**
     * @CronJob("daily")
     */
    public function onDaily(): void
    {
        $this->runLegacyCrons('daily');
    }

    /**
     * @CronJob("weekly")
     */
    public function onWeekly(): void
    {
        $this->runLegacyCrons('weekly');
    }

    /**
     * @CronJob("monthly")
     */
    public function onMonthly(): void
    {
        $this->runLegacyCrons('monthly');
    }

    /**
     * @todo Migrate our own cron jobs to the new framework
     */
    private function runLegacyCrons(string $interval): void
    {
        $this->framework->initialize();

        if (!isset($GLOBALS['TL_CRON'][$interval])) {
            return;
        }

        $system = $this->framework->getAdapter(System::class);

        // Load the default language file (see #8719)
        $system->loadLanguageFile('default');

        foreach ($GLOBALS['TL_CRON'][$interval] as $cron) {
            trigger_deprecation('contao/core-bundle', '4.9', 'Using $GLOBALS[\'TL_CRON\'] has been deprecated and will be removed in Contao 5.0. Use the "contao.cronjob" service tag instead.');

            $system->importStatic($cron[0])->{$cron[1]}();
        }
    }
}
