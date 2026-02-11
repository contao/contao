<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Asset\VersionStrategy;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\Filesystem\Path;

class MtimeVersionStrategy implements VersionStrategyInterface
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $webDir,
        private readonly string $format = '%s?v=%s',
    ) {
    }

    public function getVersion(string $path): string
    {
        // Check if path is an absolute filesystem path to an existing resource
        if (Path::isAbsolute($path) && is_file($path)) {
            return (string) filemtime($path);
        }

        $path = urldecode($path);

        // Check if path references a resource relative to the project dir
        $projectPath = Path::join($this->projectDir, $path);

        if (is_file($projectPath)) {
            return (string) filemtime($projectPath);
        }

        // Check if path references a resource relative to the public dir
        $publicPath = Path::join($this->webDir, $path);

        if (is_file($publicPath)) {
            return (string) filemtime($publicPath);
        }

        return '';
    }

    public function applyVersion(string $path): string
    {
        if (!$version = $this->getVersion($path)) {
            return $path;
        }

        $versionized = \sprintf($this->format, ltrim($path, '/'), $version);

        if ($path && '/' === $path[0]) {
            return '/'.$versionized;
        }

        return $versionized;
    }
}
