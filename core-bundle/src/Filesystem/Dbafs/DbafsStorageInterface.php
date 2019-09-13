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

use League\Flysystem\FileNotFoundException;

interface DbafsStorageInterface
{
    /**
     * Returns paths to all non-excluded files/directories in the upload path
     * in the order of their specificity (most specific first).
     *
     * @return string[]
     */
    public function listSynchronizablePaths(): \Traversable;

    /**
     * @throws FileNotFoundException     if given resource wasn't found
     * @throws \InvalidArgumentException if resource is already or cannot be excluded (logic constraint)
     */
    public function excludeFromSync(string $path): void;

    /**
     * @throws FileNotFoundException     if given resource wasn't found
     * @throws \InvalidArgumentException if resource is already or cannot be included (logic constraint)
     */
    public function includeToSync(string $path): void;

    /**
     * @throws FileNotFoundException if given resource wasn't found
     */
    public function isExcludedFromSync(string $path): bool;
}
