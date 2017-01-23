<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config\Dumper;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Combines multiple files into one PHP file.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CombinedFileDumper implements DumperInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $addNamespace;

    /**
     * @var string
     */
    private $header = "<?php\n"; // add a line-break to prevent the "unexpected $end" error

    /**
     * Constructor.
     *
     * @param Filesystem      $filesystem
     * @param LoaderInterface $loader
     * @param string          $cacheDir
     * @param bool            $addNamespace
     */
    public function __construct(Filesystem $filesystem, LoaderInterface $loader, $cacheDir, $addNamespace = false)
    {
        $this->filesystem = $filesystem;
        $this->loader = $loader;
        $this->cacheDir = $cacheDir;
        $this->addNamespace = $addNamespace;
    }

    /**
     * Sets the header for a PHP file (e.g. a file doc block).
     *
     * @param string $header
     *
     * @throws \InvalidArgumentException
     */
    public function setHeader($header)
    {
        if (0 !== strpos($header, '<?php')) {
            throw new \InvalidArgumentException('The file header must start with an opening PHP tag.');
        }

        $this->header = $header;
    }

    /**
     * {@inheritdoc}
     */
    public function dump($files, $cacheFile, array $options = [])
    {
        $type = isset($options['type']) ? $options['type'] : null;
        $buffer = $this->header;

        foreach ((array) $files as $file) {
            if ($this->addNamespace) {
                $buffer .= "\nnamespace {";
            }

            $buffer .= $this->loader->load($file, $type);

            if ($this->addNamespace) {
                $buffer .= "\n}\n";
            }
        }

        $this->filesystem->dumpFile($this->cacheDir.'/'.$cacheFile, $buffer);
    }
}
