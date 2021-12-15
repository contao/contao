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
    /**
     * Generate the hash for the content of a given file.
     *
     * If a value for $lastModified is provided, it is allowed to return null
     * instead of a hash. Doing this indicates that the hash has not changed in
     * the meantime and does not need to be recomputed.
     */
    public function hashFileContent(FilesystemAdapter $filesystem, string $path, int $lastModified = null): ?string;

    /**
     * Generate the hash for a string, preferably with the same hash function
     * that is used for hashing file contents. We use this to generate (db only)
     * directory hashes from file hashes and path names.
     */
    public function hashString(string $string): string;
}
