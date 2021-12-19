<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Dumper;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Combines multiple files into one PHP file.
 */
class CombinedFileDumper implements DumperInterface
{
    private Filesystem $filesystem;
    private LoaderInterface $loader;
    private string $cacheDir;
    private string $header = "<?php\n"; // add a line-break to prevent the "unexpected $end" error

    public function __construct(Filesystem $filesystem, LoaderInterface $loader, string $cacheDir)
    {
        $this->filesystem = $filesystem;
        $this->loader = $loader;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setHeader(string $header): void
    {
        if (0 !== strncmp($header, '<?php', 5)) {
            throw new \InvalidArgumentException('The file header must start with an opening PHP tag.');
        }

        $this->header = $header;
    }

    public function dump($files, $cacheFile, array $options = []): void
    {
        $buffer = $this->header;
        $type = $options['type'] ?? null;

        foreach ((array) $files as $file) {
            $buffer .= $this->loader->load($file, $type);
        }

        $this->filesystem->dumpFile(Path::join($this->cacheDir, $cacheFile), $buffer);
    }
}
