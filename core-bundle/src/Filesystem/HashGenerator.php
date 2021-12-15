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

class HashGenerator implements HashGeneratorInterface
{
    private string $hashAlgorithm;

    public function __construct(string $hashAlgorithm)
    {
        $this->hashAlgorithm = $hashAlgorithm;
    }

    public function hashFileContent(FilesystemAdapter $filesystem, string $path): string
    {
        $context = hash_init($this->hashAlgorithm);
        hash_update_stream($context, $filesystem->readStream($path));

        return hash_final($context);
    }

    public function hashString(string $string): string
    {
        return hash($this->hashAlgorithm, $string);
    }
}
