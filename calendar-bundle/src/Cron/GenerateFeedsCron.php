<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Cron;

use Contao\Calendar;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;

#[AsCronJob('daily')]
class GenerateFeedsCron
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();
        $this->framework->createInstance(Calendar::class)->generateFeeds();
    }
}
