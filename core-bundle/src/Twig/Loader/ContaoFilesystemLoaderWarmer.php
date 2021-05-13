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

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Webmozart\PathUtil\Path;

class ContaoFilesystemLoaderWarmer implements CacheWarmerInterface
{
    /**
     * @var ContaoFilesystemLoader
     */
    private $loader;

    /**
     * @var TemplateLocator
     */
    private $templateLocator;

    /**
     * @var string
     */
    private $projectDir;

    public function __construct(ContaoFilesystemLoader $contaoFilesystemLoader, TemplateLocator $templateLocator, string $projectDir)
    {
        $this->loader = $contaoFilesystemLoader;
        $this->templateLocator = $templateLocator;
        $this->projectDir = $projectDir;
    }

    public function warmUp(string $cacheDir): array
    {
        // Theme paths
        $themePaths = $this->templateLocator->findThemeDirectories();

        foreach ($themePaths as $slug => $path) {
            $this->loader->addPath($path, "Contao_Theme_$slug");
        }

        // Global templates path
        $globalTemplatesPath = Path::join($this->projectDir, 'templates');

        $this->loader->addPath($globalTemplatesPath, 'Contao');
        $this->loader->addPath($globalTemplatesPath, 'Contao_Global', true);

        // Bundle + app paths
        foreach ($this->templateLocator->findResourcesPaths() as $name => $resourcesPaths) {
            foreach ($resourcesPaths as $path) {
                $this->loader->addPath($path, 'Contao');
                $this->loader->addPath($path, "Contao_$name", true);
            }
        }

        $this->loader->buildHierarchy();

        $this->loader->persist();

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }
}
