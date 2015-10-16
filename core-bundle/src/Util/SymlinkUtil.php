<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Util;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Adds helper methods for symlinks.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SymlinkUtil
{
    /**
     * Generates a symlink.
     *
     * The method will try to generate relative symlinks and fall back to generating
     * absolute symlinks if relative symlinks are not supported (see #208).
     *
     * @param string $source  The symlink name
     * @param string $target  The symlink target
     * @param string $rootDir The root directory
     */
    public static function symlink($source, $target, $rootDir)
    {
        static::validateSymlink($source, $target, $rootDir);

        $fs = new Filesystem();

        if ('\\' === DIRECTORY_SEPARATOR) {
            $fs->symlink($rootDir . '/' . $source, $rootDir . '/' . $target);
        } else {
            $fs->symlink(rtrim($fs->makePathRelative($source, dirname($target)), '/'), $rootDir . '/' . $target);
        }
    }

    /**
     * Validates a symlink.
     *
     * @param string $source  The symlink name
     * @param string $target  The symlink target
     * @param string $rootDir The root directory
     *
     * @throws \InvalidArgumentException If the source or target is invalid
     * @throws \LogicException           If the target exists and is not a symlink
     */
    public static function validateSymlink($source, $target, $rootDir)
    {
        if ('' === $source) {
            throw new \InvalidArgumentException('The symlink source must not be empty.');
        }

        if ('' === $target) {
            throw new \InvalidArgumentException('The symlink target must not be empty.');
        }

        if (false !== strpos($target, '../')) {
            throw new \InvalidArgumentException('The symlink target must not be relative.');
        }

        $fs = new Filesystem();

        if ($fs->exists($rootDir . '/' . $target) && !is_link($rootDir . '/' . $target)) {
            throw new \LogicException('The symlink target "' . $target . '" exists and is not a symlink.');
        }
    }
}
