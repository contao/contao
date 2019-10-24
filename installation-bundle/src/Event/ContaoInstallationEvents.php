<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Event;

final class ContaoInstallationEvents
{
    /**
     * The contao_installation.initialize_application event is triggered in the install tool.
     *
     * @see InitializeApplicationEvent
     */
    public const INITIALIZE_APPLICATION = 'contao_installation.initialize_application';
}
