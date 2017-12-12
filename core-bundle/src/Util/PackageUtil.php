<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Util;

use PackageVersions\Versions;

class PackageUtil
{
    /**
     * Returns the version number of a package.
     *
     * @param string $packageName
     *
     * @return string
     */
    public static function getVersion(string $packageName): string
    {
        $version = Versions::getVersion($packageName);

        return static::parseVersion($version);
    }

    /**
     * Parses a version number.
     *
     * The method either returns a version number such as 1.0.0 (a leading "v"
     * will be stripped) or a branch name such as dev-master.
     *
     * @param string $version
     *
     * @return string
     */
    public static function parseVersion(string $version): string
    {
        return ltrim(strstr($version, '@', true), 'v');
    }
}
