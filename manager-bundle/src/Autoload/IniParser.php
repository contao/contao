<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Autoload;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Converts an INI configuration file into a ConfigInterface instance
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class IniParser implements ParserInterface
{
    /**
     * @var string
     */
    private $modulesDir;

    /**
     * Constructor.
     *
     * @param string $modulesDir
     */
    public function __construct($modulesDir)
    {
        $this->modulesDir = $modulesDir;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($file)
    {
        $config = new Config($file);

        $path = $this->modulesDir . '/' . $file . '/config/autoload.ini';

        if (file_exists($path)) {
            $config->setLoadAfter($this->normalize($this->parseIniFile($path)));
        }

        return [$config];
    }

    /**
     * Parses the file and returns the configuration array
     *
     * @param string $file The file path
     *
     * @return array The configuration array
     *
     * @throws \RuntimeException If the file cannot be decoded
     */
    protected function parseIniFile($file)
    {
        $ini = parse_ini_file($file, true);

        if (false === $ini) {
            throw new \RuntimeException("File $file cannot be decoded");
        }

        return $ini;
    }

    /**
     * Normalize the configuration array
     *
     * @param array $ini The configuration array
     *
     * @return array The normalized array
     */
    protected function normalize(array $ini)
    {
        if (!isset($ini['requires']) || !is_array($ini['requires'])) {
            return [];
        }

        $requires = $ini['requires'];

        // Convert optional requirements
        foreach ($requires as &$v) {
            if (0 === strpos($v, '*')) {
                $v = substr($v, 1);
            }
        }

        return $requires;
    }
}
