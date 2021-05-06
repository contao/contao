<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Loader;

use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader as BaseFilesystemLoader;

/**
 * Contao's FilesystemLoader adds the ability to add/remove paths on the fly
 * thus allowing changes without the need to rebuild the container in between.
 */
class FilesystemLoader extends BaseFilesystemLoader
{
    public function addPath($path, $namespace = self::MAIN_NAMESPACE, bool $ignoreInvalidPaths = true): void
    {
        if ($ignoreInvalidPaths) {
            try {
                parent::addPath($path, $namespace);
            } catch (LoaderError $error) {
                // Ignore
            }

            return;
        }

        parent::addPath($path, $namespace);
    }

    public function removePath(string $path, $namespace = self::MAIN_NAMESPACE): void
    {
        $path = rtrim($path, '/\\');

        if (!isset($this->paths[$namespace]) || !\in_array($path, $this->paths[$namespace], true)) {
            return;
        }

        // Invalidate the cache
        $this->cache = $this->errorCache = [];

        $this->paths[$namespace] = array_diff($this->paths[$namespace], [$path]);
    }
}
