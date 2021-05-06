<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Webmozart\PathUtil\Path;

class ContaoTwigTemplateLocator
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function getAppPath(string $twigDefaultPath): ?string
    {
        $path = Path::join($twigDefaultPath, 'contao');

        return $this->filesystem->exists($path) ? $path : null;
    }

    /**
     * @return array<string, string>
     */
    public function getAppThemePaths(string $twigDefaultPath): array
    {
        if (null === ($appPath = $this->getAppPath($twigDefaultPath))) {
            return [];
        }

        $themePaths = (new Finder())
            ->directories()
            ->in($appPath)
            ->depth('< 1')
            ->sortByName()
        ;

        $paths = [];

        /** @var SplFileInfo $path */
        foreach ($themePaths as $path) {
            $paths[$path->getBasename()] = $path->getPathname();
        }

        return $paths;
    }

    /**
     * @return array<string, string>
     */
    public function getBundlePaths(array $bundleMetadata): array
    {
        $paths = [];

        foreach ($bundleMetadata as $name => $bundle) {
            foreach (['Resources/views/contao', 'templates/contao'] as $subPath) {
                $path = Path::join($bundle['path'], $subPath);

                if ($this->filesystem->exists($path)) {
                    $paths[$name] = $path;
                }
            }
        }

        return $paths;
    }
}
