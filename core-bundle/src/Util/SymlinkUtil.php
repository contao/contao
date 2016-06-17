<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
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
     * @param string $target  The symlink target
     * @param string $link    The symlink path
     * @param string $rootDir The root directory
     */
    public static function symlink($target, $link, $rootDir)
    {
        static::validateSymlink($target, $link, $rootDir);

        $fs = new Filesystem();

        if ('\\' === DIRECTORY_SEPARATOR) {
            $fs->symlink($rootDir.'/'.$target, $rootDir.'/'.$link);
        } else {
            $fs->symlink(rtrim($fs->makePathRelative($target, dirname($link)), '/'), $rootDir.'/'.$link);
        }
    }

    /**
     * Validates a symlink.
     *
     * @param string $target  The symlink target
     * @param string $link    The symlink path
     * @param string $rootDir The root directory
     *
     * @throws \InvalidArgumentException If the source or target is invalid
     * @throws \LogicException           If the target exists and is not a symlink
     */
    public static function validateSymlink($target, $link, $rootDir)
    {
        if ('' === $target) {
            throw new \InvalidArgumentException('The symlink target must not be empty.');
        }

        if ('' === $link) {
            throw new \InvalidArgumentException('The symlink path must not be empty.');
        }

        if (false !== strpos($link, '../')) {
            throw new \InvalidArgumentException('The symlink path must not be relative.');
        }

        $fs = new Filesystem();

        if ($fs->exists($rootDir.'/'.$link) && !is_link($rootDir.'/'.$link)) {
            throw new \LogicException(sprintf('The path "%s" exists and is not a symlink.', $link));
        }
    }
}
