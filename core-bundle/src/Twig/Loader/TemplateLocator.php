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

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Exception\InvalidThemePathException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * @experimental
 */
class TemplateLocator
{
    final public const FILE_MARKER_NAMESPACE_ROOT = '.twig-root';

    private readonly Filesystem $filesystem;

    private array|null $themeDirectories = null;

    private readonly string $globalTemplateDirectory;

    public function __construct(
        private readonly string $projectDir,
        private readonly ResourceFinder $resourceFinder,
        private readonly ThemeNamespace $themeNamespace,
        private readonly Connection $connection,
    ) {
        $this->filesystem = new Filesystem();
        $this->globalTemplateDirectory = Path::join($this->projectDir, 'templates');
    }

    /**
     * @return array<string, string>
     *
     * @throws InvalidThemePathException
     */
    public function findThemeDirectories(): array
    {
        $directories = [];

        // This code might run early during cache warmup where the 'tl_theme' table
        // couldn't exist, yet.
        try {
            // Note: We cannot use models or other parts of the Contao framework here because
            // this function will be called when the container is built (see #3567)
            $themePaths = $this->connection->fetchFirstColumn("SELECT templates FROM tl_theme WHERE templates != ''");
        } catch (DriverException) {
            return [];
        }

        foreach ($themePaths as $themePath) {
            if (!is_dir($absolutePath = Path::join($this->projectDir, $themePath))) {
                continue;
            }

            $slug = $this->themeNamespace->generateSlug(Path::makeRelative($themePath, 'templates'));
            $directories[$slug] = $absolutePath;
        }

        return $this->themeDirectories = $directories;
    }

    /**
     * @return array<string, array<string>>
     */
    public function findResourcesPaths(): array
    {
        $paths = [];

        foreach (array_reverse($this->resourceFinder->getExistingSubpaths('templates')) as $name => $path) {
            $paths[$name] = [...$paths[$name] ?? [], ...$this->expandSubdirectories($path)];
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
            ->name('/(\.twig|\.html5)$/')
            ->sortByName()
        ;

        if (!$this->isNamespaceRoot($path)) {
            $finder = $finder->depth('< 1');
        }

        $templates = [];

        foreach ($finder as $file) {
            $templates[Path::normalize($file->getRelativePathname())] = Path::canonicalize($file->getPathname());
        }

        return $templates;
    }

    /**
     * Return a list of all subdirectories in $path that are not inside a directory
     * containing a namespace root marker file.
     */
    private function expandSubdirectories(string $path): array
    {
        $paths = [$path];

        if ($this->isNamespaceRoot($path)) {
            return $paths;
        }

        $namespaceRoots = [];

        $finder = (new Finder())
            ->directories()
            ->in($path)
            ->sortByName()
            ->filter(
                function (\SplFileInfo $info) use (&$namespaceRoots): bool {
                    $path = $info->getPathname();

                    foreach ($namespaceRoots as $directory) {
                        if (Path::isBasePath($directory, $path)) {
                            return false;
                        }
                    }

                    if ($this->isNamespaceRoot($path)) {
                        $namespaceRoots[] = $path;
                    }

                    return true;
                },
            )
        ;

        foreach ($finder as $item) {
            $paths[] = Path::canonicalize($item->getPathname());
        }

        return $paths;
    }

    private function isNamespaceRoot(string $path): bool
    {
        // Implicitly treat the global template directory and every theme folder as
        // namespace roots
        $defaultRoots = [
            $this->globalTemplateDirectory,
            ...($this->themeDirectories ?? $this->findThemeDirectories()),
        ];

        if (\in_array($path, $defaultRoots, true)) {
            return true;
        }

        // Require a marker file everywhere else
        return $this->filesystem->exists(Path::join($path, self::FILE_MARKER_NAMESPACE_ROOT));
    }
}
