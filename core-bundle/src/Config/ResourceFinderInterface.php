<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config;

use Symfony\Component\Finder\Finder;

interface ResourceFinderInterface
{
    /**
     * Returns a Finder object with the resource paths set.
     *
     * @return Finder
     */
    public function find();

    /**
     * Appends the subpath to the resource paths and returns a Finder object.
     *
     * @param string $subpath
     *
     * @return Finder
     */
    public function findIn($subpath);
}
