<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config\Dumper;

/**
 * Interface for cache dumpers.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface DumperInterface
{
    /**
     * Dumps files into a given cache file.
     *
     * @param array  $files     The files to be dumped
     * @param string $cacheFile The target cache file
     * @param array  $options   An options array
     */
    public function dump(array $files, $cacheFile, array $options = []);
}
