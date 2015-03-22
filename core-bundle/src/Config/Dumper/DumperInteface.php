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
 * DumperInteface
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface DumperInteface
{
    public function dump(array $files, $cacheFile, array $options = []);
}
