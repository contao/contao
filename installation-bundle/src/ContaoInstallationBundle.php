<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoInstallationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function registerCommands(Application $application): void
    {
        // disable automatic command registration
    }
}
