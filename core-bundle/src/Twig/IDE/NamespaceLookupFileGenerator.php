<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\IDE;

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class NamespaceLookupFileGenerator
{
    public const FILE_NAME = 'ide-twig.json';

    public function __construct(
        private readonly ContaoFilesystemLoader $loader,
        private Filesystem|null $filesystem = null,
    ) {
    }

    /**
     * Writes an "ide-twig.json" file with path mapping information that enables IDE
     * auto-completion for all our dynamic namespaces. The file will be dumped to the
     * given $targetDir and all contained paths will be relative to this directory.
     */
    public function write(string $targetDir): void
    {
        $mappings = [];

        foreach ($this->loader->getInheritanceChains() as $chain) {
            foreach ($chain as $path => $name) {
                [$namespace, $file] = ContaoTwigUtil::parseContaoName($name);
                $templateDir = preg_replace('%(.*)/'.preg_quote($file, '%').'%', '$1', $path);

                $mappings[Path::makeRelative($templateDir, $targetDir)] = $namespace;
            }
        }

        $data = [];

        foreach ($mappings as $path => $namespace) {
            $data['namespaces'][] = ['namespace' => 'Contao', 'path' => $path];
            $data['namespaces'][] = ['namespace' => $namespace, 'path' => $path];
        }

        if (!$this->filesystem) {
            $this->filesystem = new Filesystem();
        }

        $this->filesystem->dumpFile(
            Path::join($targetDir, self::FILE_NAME),
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }
}
