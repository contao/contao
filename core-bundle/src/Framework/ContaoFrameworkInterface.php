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
 * Interface for the Contao framework initializer.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
interface ContaoFrameworkInterface extends \Contao\CoreBundle\ContaoFrameworkInterface
{
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
