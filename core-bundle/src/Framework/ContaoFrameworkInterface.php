<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Framework;

/**
 * @deprecated Deprecated since Contao 4.7, to be removed in Contao 5.0; use the
 *             Contao\CoreBundle\Framework\ContaoFramework class instead
 */
interface ContaoFrameworkInterface extends \Contao\CoreBundle\ContaoFrameworkInterface
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

    /**
     * Creates a new instance of a given class.
     *
     * @param string $class
     * @param array  $args
     *
     * @return object
     */
    public function createInstance($class, $args = []);

    /**
     * Returns an adapter class for a given class.
     *
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return Adapter<T>&T
     *
     * @phpstan-return Adapter<T>
     */
    public function getAdapter($class);
}
