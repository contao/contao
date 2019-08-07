<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

use PackageVersions\Versions;

class PackageUtil
{
    /**
     * Returns the version number of a package.
     */
    public static function getVersion(string $packageName): string
    {
        $version = Versions::getVersion($packageName);

        return static::parseVersion($version);
    }

    /**
     * Returns the version number as "major.minor.patch".
     */
    public static function getNormalizedVersion(string $packageName): string
    {
        $chunks = explode('.', static::getVersion($packageName));
        $chunks += [0, 0, 0];

        if (\count($chunks) > 3) {
            $chunks = \array_slice($chunks, 0, 3);
        }

        return implode('.', $chunks);
    }

    /**
     * Parses a version number.
     *
     * The method either returns a version number such as 1.0.0 (a leading "v"
     * will be stripped) or a branch name such as dev-master.
     */
    public static function parseVersion(string $version): string
    {
        return ltrim(strstr($version, '@', true), 'v');
    }

    /**
     * Returns the contao/core-bundle or contao/contao version.
     */
    public static function getContaoVersion(): string
    {
        try {
            return static::getVersion('contao/core-bundle');
        } catch (\OutOfBoundsException $e) {
            return static::getVersion('contao/contao');
        }
    }
}
