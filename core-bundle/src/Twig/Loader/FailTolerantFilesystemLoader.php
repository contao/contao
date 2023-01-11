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
use Twig\Loader\FilesystemLoader;

/**
 * The FailTolerantFilesystemLoader is a drop in replacement for Twig's
 * FilesystemLoader that does not care about paths that do not exist at
 * the time they are added/prepended.
 */
class FailTolerantFilesystemLoader extends FilesystemLoader
{
    /**
     * Adds a path where templates are stored (if it exists).
     *
     * @param string $path      A path where to look for templates
     * @param string $namespace A path namespace
     */
    public function addPath(string $path, string $namespace = self::MAIN_NAMESPACE): void
    {
        try {
            parent::addPath($path, $namespace);
        } catch (LoaderError) {
            // Ignore
        }
    }

    /**
     * Prepends a path where templates are stored (if it exists).
     *
     * @param string $path      A path where to look for templates
     * @param string $namespace A path namespace
     */
    public function prependPath(string $path, string $namespace = self::MAIN_NAMESPACE): void
    {
        try {
            parent::prependPath($path, $namespace);
        } catch (LoaderError) {
            // Ignore
        }
    }
}
