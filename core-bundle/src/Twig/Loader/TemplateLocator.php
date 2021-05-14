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

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class TemplateLocator
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var array<string,string>
     */
    private $bundles;

    /**
     * @var array<string, array<string, string>>
     */
    private $bundlesMetadata;

    public function __construct(string $projectDir, array $bundles, array $bundlesMetadata)
    {
        $this->projectDir = $projectDir;
        $this->bundles = $bundles;
        $this->bundlesMetadata = $bundlesMetadata;
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
     * @return array<string, array<string>>
     */
    public function findResourcesPaths(): array
    {
        $paths = [];

        $add = function (string $group, string $basePath) use (&$paths): void {
            $paths[$group] = array_merge(
                $paths[$group] ?? [],
                $this->expandSubdirectories($basePath))
            ;
        };

        if (is_dir($path = Path::join($this->projectDir, 'contao'))) {
            $add('App', $path);
        }

        if (is_dir($path = Path::join($this->projectDir, 'src/Resources/contao'))) {
            $add('App', $path);
        }

        if (is_dir($path = Path::join($this->projectDir, 'app/Resources/contao'))) {
            $add('App', $path);
        }

        foreach (array_reverse($this->bundles) as $name => $class) {
            if (ContaoModuleBundle::class === $class) {
                $add($name, $this->bundlesMetadata[$name]['path']);
            } elseif (is_dir($path = Path::join($this->bundlesMetadata[$name]['path'], 'Resources/contao'))) {
                $add($name, $path);
            } elseif (is_dir($path = Path::join($this->bundlesMetadata[$name]['path'], 'contao'))) {
                $add($name, $path);
            }
        }

        return $paths;
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
            ->sortByName()
        ;

        $templates = [];

        foreach ($finder as $file) {
            $templates[$file->getFilename()] = $file->getPathname();
        }

        return $templates;
    }

    public static function createDirectorySlug(string $path): string
    {
        return str_replace('/', '_', Path::normalize($path));
    }

    private function expandSubdirectories(string $path): array
    {
        $finder = (new Finder())
            ->directories()
            ->in($path)
            ->sortByName()
        ;

        $paths = [
            $path,
        ];

        foreach ($finder as $item) {
            $paths[] = $item->getPathname();
        }

        return array_reverse($paths);
    }
}
