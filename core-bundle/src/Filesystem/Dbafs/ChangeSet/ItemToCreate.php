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

class ItemToCreate
{
    private string $hash;
    private string $path;
    private bool $isFile;

    /**
     * @internal
     */
    public function __construct(string $hash, string $path, bool $isFile)
    {
        $this->hash = $hash;
        $this->path = $path;
        $this->isFile = $isFile;
    }

    public function getHash(): string
    {
        return $this->hash;
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
