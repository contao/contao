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

/**
 * @experimental
 */
class ContaoFilesystemLoaderWarmer implements CacheWarmerInterface
{
    private ContaoFilesystemLoader $loader;
    private string $cacheDir;
    private string $environment;
    private ?Filesystem $filesystem;

    public function __construct(ContaoFilesystemLoader $loader, string $cacheDir, string $environment, Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem;
        $this->environment = $environment;
        $this->cacheDir = $cacheDir;
        $this->loader = $loader;
    }

    public function warmUp(string $cacheDir = null, string $buildDir = null): array
    {
        $this->loader->warmUp();

        if ('dev' === $this->environment) {
            $this->writeIdeAutoCompletionMapping($cacheDir ?? $this->cacheDir);
        }

        return [];
    }

    public function isOptional(): bool
    {
        return true;
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
