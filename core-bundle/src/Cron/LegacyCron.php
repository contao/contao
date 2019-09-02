<?php

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Cron;
use Contao\System;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class LegacyCron implements ServiceAnnotationInterface
{
    /**
     * @var ContaoFramework
     */
    protected $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @Cron("minutely")
     */
    public function onMinutely(): void
    {
        $this->runLegacyCrons('minutely');
    }

    /**
     * @Cron("hourly")
     */
    public function onHourly(): void
    {
        $this->runLegacyCrons('hourly');
    }

    /**
     * @Cron("daily")
     */
    public function onDaily(): void
    {
        $this->runLegacyCrons('daily');
    }

    /**
     * @Cron("weekly")
     */
    public function onWeekly(): void
    {
        $this->runLegacyCrons('weekly');
    }

    /**
     * @Cron("monthly")
     */
    public function onMonthly(): void
    {
        $this->runLegacyCrons('monthly');
    }

    private function runLegacyCrons(string $interval): void
    {
        $this->framework->initialize();

        if (isset($GLOBALS['TL_CRON'][$interval])) {
            /** @var System $system */
            $system = $this->framework->getAdapter(System::class);

            // Load the default language file (see #8719)
            $system->loadLanguageFile('default');

            foreach ($GLOBALS['TL_CRON'][$interval] as $cron) {
                $system->importStatic($cron[0])->{$cron[1]}();
            }
        }
    }
}
