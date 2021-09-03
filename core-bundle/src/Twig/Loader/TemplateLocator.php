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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\ThemeModel;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

/**
 * @experimental
 */
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

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(string $projectDir, array $bundles, array $bundlesMetadata, ContaoFramework $framework)
    {
        $this->projectDir = $projectDir;
        $this->bundles = $bundles;
        $this->bundlesMetadata = $bundlesMetadata;
        $this->framework = $framework;
    }

    /**
     * @return array<string, string>
     */
    public function findThemeDirectories(): array
    {
        $this->framework->initialize();

        /** @var ThemeModel $themeAdapter */
        $themeAdapter = $this->framework->getAdapter(ThemeModel::class);

        $directories = [];
        $themes = $themeAdapter->findAll() ?? [];

        foreach ($themes as $theme) {
            if (!is_dir($absolutePath = Path::join($this->projectDir, $theme->templates))) {
                continue;
            }

            try {
                $slug = self::createDirectorySlug(Path::makeRelative($theme->templates, 'templates'));
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
            $paths[$group] = array_merge(
                $paths[$group] ?? [],
                $this->expandSubdirectories($basePath)
            );
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

    /**
     * @throws InvalidThemePathException if the path contains invalid characters
     */
    public static function createDirectorySlug(string $relativePath): string
    {
        if (!Path::isRelative($relativePath)) {
            throw new \InvalidArgumentException("Path '$relativePath' must be relative.");
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
