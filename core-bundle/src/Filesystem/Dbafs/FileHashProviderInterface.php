<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

interface FileHashProviderInterface
{
    /**
     * Returns an array of [filePath → hash] mappings with the hash being null
     * if the resource is no file or could not be accessed. If the resource
     * cannot be found the entry is omitted from the result set.
     *
     * @param string[] $paths a list of resource paths
     *
     * @return string|null[] the lookup dictionary (in no particular order)
     */
    public function getHashes(array $paths): array;
}
