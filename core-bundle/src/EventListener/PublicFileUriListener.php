<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Filesystem\PublicFileUriEvent;
use League\Flysystem\FilesystemAdapter;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class PublicFileUriListener
{
    private FilesystemAdapter $localFilesAdapter;
    private string $uploadDir;

    public function __construct(FilesystemAdapter $localFilesAdapter, string $uploadDir)
    {
        $this->localFilesAdapter = $localFilesAdapter;
        $this->uploadDir = $uploadDir;
    }

    /**
     * Generate public URLs for the symlinked local files, so that they can be
     * provided directly by the web server.
     */
    public function __invoke(PublicFileUriEvent $event): void
    {
        if ($event->getAdapter() !== $this->localFilesAdapter) {
            return;
        }

        // todo: should this be an absolute path?
        $event->setPublicPath(Path::join($this->uploadDir, $event->getPath()));
    }
}
