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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Webmozart\PathUtil\Path;

/**
 * @experimental
 */
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

    /**
     * @var string
     */
    private $environment;

    public function __construct(ContaoFilesystemLoader $contaoFilesystemLoader, TemplateLocator $templateLocator, string $projectDir, string $environment)
    {
        $this->loader = $contaoFilesystemLoader;
        $this->templateLocator = $templateLocator;
        $this->projectDir = $projectDir;
        $this->environment = $environment;
    }

    public function warmUp($cacheDir = null): array
    {
        // Theme paths
        $themePaths = $this->templateLocator->findThemeDirectories();

        foreach ($themePaths as $slug => $path) {
            $this->loader->addPath($path, "Contao_Theme_$slug", true);
        }

        // Global templates path
        $globalTemplatesPath = Path::join($this->projectDir, 'templates');

        $this->loader->addPath($globalTemplatesPath);
        $this->loader->addPath($globalTemplatesPath, 'Contao_Global', true);

        // Bundle paths (including App)
        foreach ($this->templateLocator->findResourcesPaths() as $name => $resourcesPaths) {
            foreach ($resourcesPaths as $path) {
                $this->loader->addPath($path);
                $this->loader->addPath($path, "Contao_$name", true);
            }
        }

        $this->loader->buildInheritanceChains();
        $this->loader->persist();

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function refresh(): void
    {
        $this->loader->clear();

        $this->warmUp();
    }

    /**
     * Auto refresh in dev mode.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ('dev' === $this->environment) {
            $this->refresh();
        }
    }
}
