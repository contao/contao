<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Dumper;

interface DumperInterface
{
    /**
     * Dumps files into a given cache file.
     *
     * @param string $cacheFile
     */
    public function dump(array|string $files, /*string */$cacheFile, array $options = []);
}
