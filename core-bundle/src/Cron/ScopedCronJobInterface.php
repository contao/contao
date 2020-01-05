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

interface ScopedCronJobInterface
{
    /**
     * Sets the scope for the cron job.
     */
    public function setScope(string $scope);
}
