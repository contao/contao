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

use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class TemplateLocator
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @return array<string, string>
     */
    public function findThemeDirectories(): array
    {
        if (!is_dir($path = Path::join($this->projectDir, 'templates'))) {
            return [];
        }

        $finder = (new Finder())
            ->directories()
            ->in($path)
            ->sortByName()
        ;

        $directories = [];

        foreach ($finder as $directory) {
            $slug = self::createDirectorySlug($directory->getRelativePathname());

            $directories[$slug] = $directory->getPathname();
        }

        return $directories;
    }

    /**
     * @return array<string, string>
     */
    public function findTemplates(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $finder = (new Finder())
            ->files()
            ->in($path)
            ->depth('< 1')
            ->name('*.html.twig')
        ;

        $templates = [];

        foreach ($finder as $file) {
            $templates[$file->getFilename()] = $file->getPathname();
        }

        ksort($templates);

        return $templates;
    }

    public static function createDirectorySlug(string $path): string
    {
        return str_replace('/', '_', Path::normalize($path));
    }
}
