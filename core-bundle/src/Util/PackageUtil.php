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

use Composer\InstalledVersions;

/**
 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0; use
 *             the Composer\InstalledVersions class instead
 */
class PackageUtil
{
    /**
     * Returns the version number of a package.
     */
    public static function getVersion(string $packageName): string
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using the PackageUtil::getVersion() method has been deprecated and will no longer work in Contao 5.0. Use the Composer\InstalledVersions class instead.');

        $version = InstalledVersions::getPrettyVersion($packageName) ?? '';

        return static::parseVersion($version);
    }

    /**
     * Returns the version number as "major.minor.patch".
     */
    public static function getNormalizedVersion(string $packageName): string
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using the PackageUtil::getNormalizedVersion() method has been deprecated and will no longer work in Contao 5.0. Use the Composer\InstalledVersions class instead.');

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
     * will be stripped) or a branch name such as dev-main.
     */
    public static function parseVersion(string $version): string
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using the PackageUtil::parseVersion() method has been deprecated and will no longer work in Contao 5.0. Use the Composer\InstalledVersions class instead.');

        return ltrim(strstr($version.'@', '@', true), 'v');
    }

    /**
     * Returns the contao/core-bundle or contao/contao version.
     */
    public static function getContaoVersion(): string
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using the PackageUtil::getContaoVersion() method has been deprecated and will no longer work in Contao 5.0. Use the ContaoCoreBundle::getVersion() method instead.');

        try {
            $version = static::getVersion('contao/core-bundle');
        } catch (\OutOfBoundsException $e) {
            $version = '';
        }

        if ('' === $version) {
            $version = static::getVersion('contao/contao');
        }

        return $version;
    }
}
