<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

use Contao\CoreBundle\ContaoFrameworkInterface as OldFrameworkInterface;

/**
 * Contao framework interface.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
interface ContaoFrameworkInterface extends OldFrameworkInterface
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

    /**
     * Creates a new instance of a given class.
     *
     * @param string $class The fully qualified class name
     * @param array  $args  Optional constructor arguments
     *
     * @return object The instance
     */
    public function createInstance($class, $args = []);

    /**
     * Returns an adapter class for a given class.
     *
     * @param string $class The fully qualified class name
     *
     * @return Adapter The adapter class
     */
    public function getAdapter($class);
}
