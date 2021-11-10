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
use Webmozart\PathUtil\Path;

class ContaoDirectoryLocator
{
    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(Filesystem $filesystem, string $projectDir)
    {
        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;
    }

    public function getConfigDirectory(): string
    {
        $directory = Path::join($this->projectDir, 'contao');

        // Make sure the directory exists
        $this->filesystem->mkdir($directory);

        return $directory;
    }
}
