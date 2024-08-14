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
use Symfony\Component\Filesystem\Path;

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

        $target = Path::makeAbsolute($target, $projectDir);
        $link = Path::makeAbsolute($link, $projectDir);

        $fs = new Filesystem();

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $fs->symlink($target, $link);
        } else {
            $fs->symlink(Path::makeRelative($target, Path::getDirectory($link)), $link);
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

        $link = Path::normalize($link);

        if (str_contains($link, '../')) {
            throw new \InvalidArgumentException('The symlink path must not be relative.');
        }

        $linkPath = Path::join($projectDir, $link);

        if (!is_link($linkPath) && (new Filesystem())->exists($linkPath)) {
            throw new \LogicException(\sprintf('The path "%s" exists and is not a symlink.', $link));
        }
    }
}
