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

        if (!empty($json['bundles'])) {
            $this->parseBundles($json['bundles'], $configs);
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

    /**
     * Parses the bundle array and generates config objects.
     *
     * @param array $bundles
     * @param array $configs
     *
     * @throws \RuntimeException
     */
    private function parseBundles(array $bundles, array &$configs)
    {
        foreach ($bundles as $options) {
            // Only one value given, must be class name
            if (!is_array($options)) {
                $options = ['name' => $options];
            }

            if (!isset($options['name'])) {
                throw new \RuntimeException(sprintf('Missing name for bundle config (%s)', json_encode($options)));
            }

            $config = new Config($options['name']);

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
    }
}
