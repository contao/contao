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

use Contao\Automator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\CronJob;

/**
 * @CronJob("daily")
 */
class PurgeTempFolderCron
{
    public function __construct(private ContaoFramework $framework)
    {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();
        $this->framework->createInstance(Automator::class)->purgeTempFolder();
    }
}
