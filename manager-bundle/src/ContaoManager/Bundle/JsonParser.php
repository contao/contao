<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle;

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
    public function parse($resource, $type = null)
    {
        $configs = [];
        $json = $this->parseJsonFile($resource);

        $this->parseBundles($json, $configs);

        return $configs;
    }

    /**
     * @inheritdoc
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'json' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Parses the file and returns the configuration array
     *
     * @param string $file The absolute file path
     *
     * @return array The configuration array
     *
     * @throws \InvalidArgumentException If $file is not a file
     * @throws \RuntimeException         If the file cannot be decoded
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

            $config = new BundleConfig($options['name']);

            if (isset($options['replace'])) {
                $config->setReplace($options['replace']);
            }

            if (isset($options['development'])) {
                if (true === $options['development']) {
                    $config->setLoadInProduction(false);
                } elseif (false === $options['development']) {
                    $config->setLoadInDevelopment(false);
                }
            }

            if (isset($options['load-after'])) {
                $config->setLoadAfter($options['load-after']);
            }

            $configs[] = $config;
        }
    }
}
