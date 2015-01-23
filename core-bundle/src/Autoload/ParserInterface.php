<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Autoload;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Configuration parser interface.
 *
 * @author Leo Feyer <https://contao.org>
 */
interface ParserInterface
{
    /**
     * Parses a configuration file and returns the normalized configuration array.
     *
     * @param SplFileInfo $file The file object
     *
     * @return array The normalized configuration array
     */
    public function parse(SplFileInfo $file);
}
