<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config;

use Symfony\Component\Finder\Finder;

/**
 * Interface for resource finders.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface ResourceFinderInterface
{
    /**
     * Returns a Finder object with the resource paths set.
     *
     * @return Finder The Finder object
     */
    public function find();

    /**
     * Appends the subpath to the resource paths and returns a Finder object.
     *
     * @param string $subpath The subpath
     *
     * @return Finder The Finder object
     */
    public function findIn($subpath);
}
