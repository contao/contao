<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs\ChangeSet;

class ItemToDelete
{
    /**
     * @internal
     */
    public function __construct(private string $path, private bool $isFile)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isFile(): bool
    {
        return $this->isFile;
    }

    public function isDirectory(): bool
    {
        return !$this->isFile;
    }
}
