<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config\Dumper;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CombinedFileDumper combines multiple files into one PHP file
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CombinedFileDumper implements DumperInterface
{
    private $loader;
    private $cacheDir;
    private $filesystem;
    private $header = '<?php '; // add one space to prevent the "unexpected $end" error

    /**
     * Constructor.
     *
     * @param Filesystem      $filesystem A filesystem abstraction
     * @param LoaderInterface $loader     A loader to get PHP content from the files
     * @param string          $cacheDir   The base directory where to put cache files
     */
    public function __construct(Filesystem $filesystem, LoaderInterface $loader, $cacheDir)
    {
        $this->filesystem = $filesystem;
        $this->loader     = $loader;
        $this->cacheDir   = $cacheDir;
    }

    /**
     * Sets header for PHP files (e.g. a file docblock)
     *
     * @param string $header The file header
     */
    public function setHeader($header)
    {
        if (strpos($header, '<?php') !== 0) {
            throw new \InvalidArgumentException('File header must start with a PHP open tag.');
        }

        $this->header = $header;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(array $files, $cacheFile, array $options = [])
    {
        $type   = isset($options['type']) ? $options['type'] : null;
        $buffer = $this->header;

        foreach ($files as $file) {
            $buffer .= $this->loader->load($file, $type);
        }

        $this->filesystem->dumpFile($this->cacheDir . DIRECTORY_SEPARATOR . $cacheFile, $buffer);
    }
}
