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

interface HashGeneratorInterface
{
    public function hashFileContent(FilesystemAdapter $filesystem, string $path): string;

    public function hashString(string $string): string;
}
