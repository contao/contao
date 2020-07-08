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

use Symfony\Component\Filesystem\Filesystem;

class SymlinkUtil
{
    /**
     * Generates a symlink.
     *
     * The method will try to generate relative symlinks and fall back to generating
     * absolute symlinks if relative symlinks are not supported (see #208).
     */
    public static function symlink(string $target, string $link, string $projectDir): void
    {
        static::validateSymlink($target, $link, $projectDir);

        $fs = new Filesystem();

        if (!$fs->isAbsolutePath($target)) {
            $target = $projectDir.'/'.$target;
        }

        if (!$fs->isAbsolutePath($link)) {
            $link = $projectDir.'/'.$link;
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $fs->symlink($target, $link);
        } else {
            $fs->symlink(rtrim($fs->makePathRelative($target, \dirname($link)), '/'), $link);
        }
    }

    /**
     * Validates a symlink.
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public static function validateSymlink(string $target, string $link, string $projectDir): void
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

        if ($fs->exists($projectDir.'/'.$link) && !is_link($projectDir.'/'.$link)) {
            throw new \LogicException(sprintf('The path "%s" exists and is not a symlink.', $link));
        }
    }
}
