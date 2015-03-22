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
    private $header = '<?php '; // add one space to prevent the "unexpected $end" error

    /**
     * Constructor.
     *
     * @param LoaderInterface $loader   A loader to get PHP content from the files
     * @param string          $cacheDir The base directory where to put cache files
     */
    public function __construct(LoaderInterface $loader, $cacheDir)
    {
        $realDir = realpath($cacheDir);

        if (false === $realDir) {
            throw new \InvalidArgumentException(sprintf('Cache directory not found (in %s)', $cacheDir));
        }

        $this->loader   = $loader;
        $this->cacheDir = $realDir;
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
        $buffer = $this->header;

        foreach ($files as $file) {
            $buffer .= $this->loader->load($file, $options['type']);
        }

        $this->createDirectory(dirname($cacheFile));

        file_put_contents($this->cacheDir . DIRECTORY_SEPARATOR . $cacheFile, $buffer, LOCK_EX);
    }

    /**
     * Recursively creates a folder if it does not exist.
     *
     * @param string $folder The folder to create, relative to the cache directory
     */
    private function createDirectory($folder)
    {
        if (empty($folder) || is_dir($this->cacheDir . DIRECTORY_SEPARATOR . $folder)) {
            return;
        }

        $relativePath = '';

        // Create the folder
        foreach (array_filter(explode('/', $folder)) as $name) {
            $relativePath .= $name . DIRECTORY_SEPARATOR;

            if (!is_dir($this->cacheDir . DIRECTORY_SEPARATOR . $relativePath)) {
                mkdir($this->cacheDir . DIRECTORY_SEPARATOR . $relativePath);
            }
        }
    }
}
