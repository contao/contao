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
 * Converts a JSON configuration file into a configuration array
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class JsonParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse($file)
    {
        $configs = [];
        $json = $this->parseJsonFile($file);

        foreach ($json['bundles'] as $class => &$options) {
            $ref = new \ReflectionClass($class);

            $config = new Config($ref->getShortName());
            $config->setClass($class);

            if (isset($options['replace'])) {
                $config->setReplace($options['replace']);
            }

            if (isset($options['environments'])) {
                $config->setEnvironments($options['environments']);
            }

            if (isset($options['load-after'])) {
                $config->setLoadAfter($options['load-after']);
            }

            $configs[] = $config;
        }

        return $configs;
    }

    /**
     * Parses the file and returns the configuration array
     *
     * @param string $file The absolute file path
     *
     * @return array The configuration array
     *
     * @throws \InvalidArgumentException If $file is not a file
     * @throws \RuntimeException         If the file cannot be decoded or there are no bundles
     */
    private function parseJsonFile($file)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException("$file is not a file");
        }

        $json = json_decode(file_get_contents($file), true);

        if (null === $json) {
            throw new \RuntimeException("File $file cannot be decoded");
        }

        if (empty($json['bundles'])) {
            throw new \RuntimeException("No bundles defined in $file");
        }

        return $json;
    }
}
