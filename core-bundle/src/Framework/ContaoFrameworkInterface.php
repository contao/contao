<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

/**
 * Interface for the Contao framework.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
interface ContaoFrameworkInterface extends \Contao\CoreBundle\ContaoFrameworkInterface
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
     * @param string $class Fully qualified class name.
     * @param array $args Constructor arguments.
     *
     * @return mixed
     */
    public function createInstance($class, $args = []);

    /**
     * Returns an adapter class for a given class.
     *
     * @param string $class Fully qualified class name.
     *
     * @return mixed
     */
    public function getAdapter($class);
}
