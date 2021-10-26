<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Filesystem;

use Symfony\Component\Filesystem\Filesystem;

class ContaoDirectoryLocator
{
    private Filesystem $filesystem;
    private string $projectDirectory;

    public function __construct(Filesystem $filesystem, string $projectDirectory)
    {
        $this->filesystem = $filesystem;
        $this->projectDirectory = $projectDirectory;
    }

    public function getConfigDirectory(): string
    {
        $directory = sprintf('%s/contao', $this->projectDirectory);

        // Make sure the directory exists
        $this->filesystem->mkdir($directory);

        return $directory;
    }
}
