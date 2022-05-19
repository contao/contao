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

use Contao\CoreBundle\Exception\InvalidThemePathException;
use Symfony\Component\Filesystem\Path;

class ThemeNamespace
{
    /**
     * Generates a theme slug from a relative path.
     *
     * @throws InvalidThemePathException if the path contains invalid characters
     */
    public function generateSlug(string $relativePath): string
    {
        if (!Path::isRelative($relativePath)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" must be relative.', $relativePath));
        }

        $path = Path::normalize($relativePath);
        $invalidCharacters = [];

        $slug = implode('_', array_map(
            static function (string $chunk) use (&$invalidCharacters) {
                // Allow paths outside the template directory (see #3271)
                if ('..' === $chunk) {
                    return '';
                }

                // Check for invalid characters (see #3354)
                if (0 !== preg_match_all('%[^a-zA-Z0-9-]%', $chunk, $matches)) {
                    $invalidCharacters = array_merge($invalidCharacters, $matches[0]);
                }

                return $chunk;
            },
            explode('/', $path)
        ));

        if (!empty($invalidCharacters)) {
            throw new InvalidThemePathException($path, $invalidCharacters);
        }

        return $slug;
    }

    /**
     * Builds the namespace for a certain theme slug.
     */
    public function getFromSlug(string $slug): string
    {
        return "@Contao_Theme_$slug";
    }

    /**
     * Extracts a theme slug from a given logical name.
     *
     * @return string the theme slug or null if not a theme namespace
     */
    public function match(string $logicalName): string|null
    {
        if (1 === preg_match('%^@Contao_Theme_([a-zA-Z0-9_-]+)/%', $logicalName, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
