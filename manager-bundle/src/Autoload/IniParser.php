<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Autoload;

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
        $configs = [];
        $config = new ModuleConfig($file);
        $configs[] = $config;

        $path = $this->modulesDir . '/' . $file . '/config/autoload.ini';

        if (file_exists($path)) {
            $requires = $this->parseIniFile($path);

            if (0 !== count($requires)) {
                // Recursively load all modules that are required by other modules
                foreach ($requires as &$module) {
                    $optional = false;

                    if (0 === strpos($module, '*')) {
                        $optional = true;
                        $module = substr($module, 1);
                    }

                    // Do not add optional modules that are not installed, ContaoModuleBundle would throw exception
                    if ($optional && !is_dir($this->modulesDir . '/' . $module)) {
                        continue;
                    }

                    $configs = array_merge($configs, $this->parse($module));
                }

                $config->setLoadAfter($requires);
            }
        }

        return $configs;
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

        if (!isset($ini['requires']) || !is_array($ini['requires'])) {
            return [];
        }

        return $ini['requires'];
    }
}
