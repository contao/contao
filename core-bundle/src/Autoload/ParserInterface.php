<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Autoload;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Configuration parser interface
 *
 * @author Leo Feyer <https://contao.org>
 */
interface ParserInterface
{
    /**
     * Parses a configuration file
     *
     * @param SplFileInfo $file The file object
     *
     * @return array A normalized configuration array
     */
    public function parse(SplFileInfo $file);
}
