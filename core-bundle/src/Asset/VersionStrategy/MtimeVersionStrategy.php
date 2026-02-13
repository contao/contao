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
        private readonly string $webDir,
        private readonly string $format = '%s?v=%s',
    ) {
    }

    public function getVersion(string $path): string
    {
        return (string) @filemtime(Path::join($this->webDir, urldecode($path)));
    }

    public function applyVersion(string $path): string
    {
        if (!$version = $this->getVersion($path)) {
            return $path;
        }

        return \sprintf($this->format, $path, $version);
    }
}
