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

use Symfony\Component\Filesystem\Filesystem;

/**
 * Adds helper methods for symlinks.
 */
class SymlinkUtil
{
    /**
     * Generates a symlink.
     *
     * The method will try to generate relative symlinks and fall back to generating
     * absolute symlinks if relative symlinks are not supported (see #208).
     *
     * @param string $target
     * @param string $link
     * @param string $rootDir
     */
    public static function symlink(string $target, string $link, string $rootDir): void
    {
        static::validateSymlink($target, $link, $rootDir);

        $fs = new Filesystem();

        if (!$fs->isAbsolutePath($target)) {
            $target = $rootDir.'/'.$target;
        }

        if (!$fs->isAbsolutePath($link)) {
            $link = $rootDir.'/'.$link;
        }

        if ('\\' === DIRECTORY_SEPARATOR) {
            $fs->symlink($target, $link);
        } else {
            $fs->symlink(rtrim($fs->makePathRelative($target, dirname($link)), '/'), $link);
        }
    }

    /**
     * Validates a symlink.
     *
     * @param string $target
     * @param string $link
     * @param string $rootDir
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public static function validateSymlink(string $target, string $link, string $rootDir): void
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
