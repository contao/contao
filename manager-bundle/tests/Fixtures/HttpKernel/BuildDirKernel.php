<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Fixtures\HttpKernel;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Filesystem\Path;

class BuildDirKernel extends ContaoKernel
{
    public function getBuildDir(): string
    {
        return Path::join($this->getProjectDir(), 'var/build', $this->getEnvironment());
    }
}
