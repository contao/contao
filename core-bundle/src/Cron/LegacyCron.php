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
use Contao\CoreBundle\ServiceAnnotation\Cron;
use Contao\System;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class LegacyCron implements ServiceAnnotationInterface
{
    private const ALLOWED_CRONS = [
        'generateCalendarFeeds',
        'purgeCommentSubscriptions',
        'purgeTempFolder',
        'purgeSearchCache',
        'generateSitemap',
        'purgeRegistrations',
        'purgeOptInTokens',
        'generateNewsFeeds',
        'purgeNewsletterSubscriptions',
    ];

    /**
     * @var ContaoFramework
     */
    private $framework;

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

            foreach ($GLOBALS['TL_CRON'][$interval] as $name => $cron) {
                if (!\in_array($name, self::ALLOWED_CRONS, true)) {
                    @trigger_error('Using $GLOBALS[\'TL_CRON\'] has been deprecated and will be removed in Contao 5.0. Use the "contao.cron" service tag instead.', E_USER_DEPRECATED);
                }

                $system->importStatic($cron[0])->{$cron[1]}();
            }
        }
    }
}
