<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs\Hashing;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;

/**
 * @experimental
 */
interface HashGeneratorInterface
{
    /**
     * Generates the hash for the content of a given file.
     *
     * Use the $context to check if hashing may be skipped, to get additional metadata
     * and to set the actual result of the operation.
     */
    public function hashFileContent(VirtualFilesystemInterface $filesystem, string $path, Context $context): void;

    /**
     * Generates the hash for a string, preferably with the same hash function that is
     * used for hashing file contents.
     *
     * We use this to generate (db only) directory hashes from file hashes and path names.
     */
    public function hashString(string $string): string;
}
