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

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @experimental
 */
class ContaoFilesystemLoaderWarmer implements CacheWarmerInterface
{
    private ContaoFilesystemLoader $loader;
    private TemplateLocator $templateLocator;
    private string $projectDir;
    private string $cacheDir;
    private string $environment;
    private ?Filesystem $filesystem;

    public function __construct(ContaoFilesystemLoader $contaoFilesystemLoader, TemplateLocator $templateLocator, string $projectDir, string $cacheDir, string $environment, Filesystem $filesystem = null)
    {
        $this->loader = $contaoFilesystemLoader;
        $this->templateLocator = $templateLocator;
        $this->projectDir = $projectDir;
        $this->cacheDir = $cacheDir;
        $this->environment = $environment;
        $this->filesystem = $filesystem;
    }

    public function warmUp(string $cacheDir = null): array
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

        if ('dev' === $this->environment) {
            $this->writeIdeAutoCompletionMapping($cacheDir ?? $this->cacheDir);
        }

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
        if ('dev' === $this->environment && $event->isMainRequest()) {
            $this->refresh();
        }
    }

    /**
     * Writes an "ide-twig.json" file with path mapping information that
     * enables IDE auto-completion for all our dynamic namespaces.
     */
    private function writeIdeAutoCompletionMapping(string $cacheDir): void
    {
        $mappings = [];
        $targetDir = Path::join($cacheDir, 'contao');

        foreach ($this->loader->getInheritanceChains() as $chain) {
            foreach ($chain as $path => $name) {
                $mappings[Path::getDirectory(Path::makeRelative($path, $targetDir))] = ContaoTwigUtil::parseContaoName($name)[0];
            }
        }

        $data = [];

        foreach ($mappings as $path => $namespace) {
            $data['namespaces'][] = ['namespace' => 'Contao', 'path' => $path];
            $data['namespaces'][] = compact('namespace', 'path');
        }

        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }

        try {
            $this->filesystem->dumpFile(
                Path::join($targetDir, 'ide-twig.json'),
                json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
            );
        } catch (IOException $e) {
            // ignore
        }
    }
}
