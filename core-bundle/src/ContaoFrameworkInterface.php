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
 *
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.
 *             Use the interface in the Framework namespace.
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
     */
    public function initialize();
}
