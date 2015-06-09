<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

/**
 * Interface for the Contao framework initializer.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
interface ContaoFrameworkInterface
{
    /**
     * Checks if the framework has been initialized.
     *
     * @return bool True if the framework has been initialized
     */
    public function isInitialized();

    /**
     * Initializes the framework.
     *
     * @throws \LogicException If the container is not set
     */
    public function initialize();
}
