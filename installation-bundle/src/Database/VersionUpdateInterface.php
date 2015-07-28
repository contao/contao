<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

/**
 * Interface for version update classes.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface VersionUpdateInterface
{
    /**
     * Checks whether the update should be run.
     *
     * @return bool True if the update should be run
     */
    public function shouldBeRun();

    /**
     * Runs the update.
     */
    public function run();
}
