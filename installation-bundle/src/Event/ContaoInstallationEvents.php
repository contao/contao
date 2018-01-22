<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Event;

/**
 * Defines constants for the Contao installation events.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
final class ContaoInstallationEvents
{
    /**
     * The contao_installation.initialize_application event is triggered in the install tool.
     *
     * @var string
     *
     * @see InitializeApplicationEvent
     */
    const INITIALIZE_APPLICATION = 'contao_installation.initialize_application';
}
