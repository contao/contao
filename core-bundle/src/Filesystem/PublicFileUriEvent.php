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

use League\Flysystem\FilesystemAdapter;

class PublicFileUriEvent
{
    private FilesystemAdapter $adapter;
    private string $path;
    private PublicFileUriOptions $options;

    private ?string $publicPath = null;

    public function __construct(FilesystemAdapter $adapter, string $path, PublicFileUriOptions $options)
    {
        $this->adapter = $adapter;
        $this->path = $path;
        $this->options = $options;
    }

    public function getAdapter(): FilesystemAdapter
    {
        return $this->adapter;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getOptions(): PublicFileUriOptions
    {
        return $this->options;
    }

    public function getPublicPath(): ?string
    {
        return $this->publicPath;
    }

    public function setPublicPath(?string $publicPath): void
    {
        $this->publicPath = $publicPath;
    }
}
