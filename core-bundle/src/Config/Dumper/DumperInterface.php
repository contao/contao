<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config\Dumper;

interface DumperInterface
{
    /**
     * Dumps files into a given cache file.
     *
     * @param array|string $files
     * @param string       $cacheFile
     * @param array        $options
     */
    public function dump($files, $cacheFile, array $options = []);
}
