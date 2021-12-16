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
    private bool $useLastModified;

    public function __construct(string $hashAlgorithm, bool $useLastModified = false)
    {
        $this->hashAlgorithm = $hashAlgorithm;
        $this->useLastModified = $useLastModified;
    }

    public function hashFileContent(FilesystemAdapter $filesystem, string $path, int $lastModified = null, int &$fileLastModified = null): ?string
    {
        if ($this->useLastModified) {
            $fileLastModified = $filesystem->lastModified($path)->lastModified();

            // Skip generating hashes if possible
            if (null !== $lastModified && $fileLastModified === $lastModified) {
                return null;
            }
        }

        $context = hash_init($this->hashAlgorithm);
        hash_update_stream($context, $filesystem->readStream($path));

        return hash_final($context);
    }

    public function hashString(string $string): string
    {
        return hash($this->hashAlgorithm, $string);
    }
}
