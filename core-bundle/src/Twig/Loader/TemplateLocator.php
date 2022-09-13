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
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * @experimental
 */
class TemplateLocator
{
    private string $projectDir;
    private ThemeNamespace $themeNamespace;
    private Connection $connection;

    /**
     * @var array<string,string>
     */
    private array $bundles;

    /**
     * @var array<string, array<string, string>>
     */
    private array $bundlesMetadata;

    public function __construct(string $projectDir, array $bundles, array $bundlesMetadata, ThemeNamespace $themeNamespace, Connection $connection)
    {
        $this->projectDir = $projectDir;
        $this->bundles = $bundles;
        $this->bundlesMetadata = $bundlesMetadata;
        $this->themeNamespace = $themeNamespace;
        $this->connection = $connection;
    }

    /**
     * @return array<string, string>
     */
    public function findThemeDirectories(): array
    {
        $directories = [];

        // This code might run early during cache warmup where the 'tl_theme'
        // table couldn't exist, yet.
        try {
            // Note: We cannot use models or other parts of the Contao
            // framework here because this function will be called when the
            // container is built (see #3567)
            $themePaths = $this->connection->fetchFirstColumn("SELECT templates FROM tl_theme WHERE templates != ''");
        } catch (DriverException $e) {
            return [];
        }

        foreach ($themePaths as $themePath) {
            if (!is_dir($absolutePath = Path::join($this->projectDir, $themePath))) {
                continue;
            }

            try {
                $slug = $this->themeNamespace->generateSlug(Path::makeRelative($themePath, 'templates'));
            } catch (InvalidThemePathException $e) {
                trigger_deprecation('contao/core-bundle', '4.12', 'Using a theme path with invalid characters has been deprecated and will throw an exception in Contao 5.0.');

                continue;
            }

            $directories[$slug] = $absolutePath;
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
            $paths[$group] = array_merge($paths[$group] ?? [], $this->expandSubdirectories($basePath));
        };

        if (is_dir($path = Path::join($this->projectDir, 'contao/templates'))) {
            $add('App', $path);
        }

        if (is_dir($path = Path::join($this->projectDir, 'src/Resources/contao/templates'))) {
            $add('App', $path);
        }

        if (is_dir($path = Path::join($this->projectDir, 'app/Resources/contao/templates'))) {
            $add('App', $path);
        }

        foreach (array_reverse($this->bundles) as $name => $class) {
            if (ContaoModuleBundle::class === $class && is_dir($path = Path::join($this->bundlesMetadata[$name]['path'], 'templates'))) {
                $add($name, $path);
            } elseif (is_dir($path = Path::join($this->bundlesMetadata[$name]['path'], 'Resources/contao/templates'))) {
                $add($name, $path);
            } elseif (is_dir($path = Path::join($this->bundlesMetadata[$name]['path'], 'contao/templates'))) {
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
            ->name('/(\.html\.twig|\.html5)$/')
            ->sortByName()
        ;

        $templates = [];

        foreach ($finder as $file) {
            $templates[$file->getFilename()] = Path::canonicalize($file->getPathname());
        }

        return $templates;
    }

    private function expandSubdirectories(string $path): array
    {
        $finder = (new Finder())
            ->directories()
            ->in($path)
            ->sortByName()
        ;

        $paths = [$path];

        foreach ($finder as $item) {
            $paths[] = Path::canonicalize($item->getPathname());
        }

        return $paths;
    }
}
