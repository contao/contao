<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Asset;

use Symfony\Component\Asset\PathPackage;

/**
 * @internal
 */
class ContaoPackage extends PathPackage
{
    public function getUrl(string $path): string
    {
        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        $versionedPath = $this->getVersionStrategy()->applyVersion($path);

        if ($this->isAbsoluteUrl($versionedPath)) {
            return $versionedPath;
        }

        if (str_starts_with($versionedPath, '/')) {
            return $this->getContext()->getBasePath().$versionedPath;
        }

        return $this->getBasePath().$versionedPath;
    }
}
