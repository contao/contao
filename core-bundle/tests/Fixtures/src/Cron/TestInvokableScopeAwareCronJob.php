<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Cron;

use Contao\CoreBundle\Cron\ScopeAwareCronJobInterface ;

class TestInvokableScopeAwareCronJob implements ScopeAwareCronJobInterface 
{
    public function __invoke(): void
    {
    }

    public function setScope(string $scope): void
    {
    }
}
