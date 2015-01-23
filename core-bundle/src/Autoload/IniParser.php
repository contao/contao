<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Autoload;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Converts an .ini configuration file into a configuration array.
 *
 * @author Leo Feyer <https://contao.org>
 */
class IniParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse(SplFileInfo $file)
    {
        return ['bundles' => [$this->doParse($file)]];
    }

    /**
     * Parses a configuration file and returns the normalized configuration array.
     *
     * @param SplFileInfo $file The file object
     *
     * @return array The normalized configuration array
     */
    protected function doParse(SplFileInfo $file)
    {
        $options = [
            'class'        => null,
            'name'         => $file->getBasename(),
            'replace'      => [],
            'environments' => ['all'],
            'load-after'   => [],
        ];

        $path = $file . '/config/autoload.ini';

        if (file_exists($path)) {
            $options['load-after'] = $this->normalize($this->parseIniFile($path));
        }

        return $options;
    }

    /**
     * Parses an .ini file and returns the configuration array.
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
     * Normalizes the configuration array.
     *
     * @param array $ini The configuration array
     *
     * @return array The normalized configuration array
     */
    protected function normalize(array $ini)
    {
        if (!$this->hasRequires($ini)) {
            return [];
        }

        $requires = $ini['requires'];

        // Convert optional requirements
        foreach ($requires as &$v) {
            if (0 === strncmp($v, '*', 1)) {
                $v = substr($v, 1);
            }
        }

        return $requires;
    }

    /**
     * Checks whether the configuration contains a "requires" section.
     *
     * @param array $ini The configuration array
     *
     * @return bool True if there is a "requires" section
     */
    protected function hasRequires(array $ini)
    {
        return isset($ini['requires']) && is_array($ini['requires']);
    }
}
