<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;

/**
 * @experimental
 */
class ViewOperationContext
{
    /**
     * @internal
     */
    public function __construct(
        private readonly string $viewPath,
        private readonly VirtualFilesystemInterface $storage,
    ) {
    }

    public function getViewPath(): string
    {
        return $this->viewPath;
    }

    public function getStorage(): VirtualFilesystemInterface
    {
        return $this->storage;
    }
}
