<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Hashing;

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

    public function hashFileContent(FilesystemAdapter $filesystem, string $path, Context $context): void
    {
        if ($this->useLastModified) {
            $context->updateLastModified($filesystem->lastModified($path)->lastModified());

            // Skip generating hashes if possible
            if ($context->canSkipHashing() && $context->hasLastModifiedChanged()) {
                $context->skipHashing();

                return;
            }
        }

        $context->setHash($this->generateFileContentHash($filesystem, $path));
    }

    public function hashString(string $string): string
    {
        return hash($this->hashAlgorithm, $string);
    }

    private function generateFileContentHash(FilesystemAdapter $filesystem, string $path): string
    {
        $hashContext = hash_init($this->hashAlgorithm);
        hash_update_stream($hashContext, $filesystem->readStream($path));

        return hash_final($hashContext);
    }
}
