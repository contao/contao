<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;

/**
 * @experimental
 */
class VirtualFilesystemFactory
{
    /**
     * @internal
     */
    public function __construct(
        private MountManager $mountManager,
        private DbafsManager $dbafsManager,
    ) {
    }

    public function __invoke(string $prefix = '', bool $readonly = false): VirtualFilesystem
    {
        return new VirtualFilesystem($this->mountManager, $this->dbafsManager, $prefix, $readonly);
    }
}
