<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

/**
 * Contao framework interface.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5; use the
 *             Contao\CoreBundle\Framework\ContaoFrameworkInterface interface instead
 */
interface ContaoFrameworkInterface
{
    /**
     * Checks if the framework has been initialized.
     *
     * @return bool
     */
    public function isInitialized();

    /**
     * Initializes the framework.
     */
    public function initialize();
}
