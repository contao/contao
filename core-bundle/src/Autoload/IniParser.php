<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Autoload;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Converts an INI configuration file into a configuration array
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
        return [
            'bundles' => [$this->doParse($file)]
        ];
    }

    /**
     * Parses the file and returns the options array
     *
     * @param SplFileInfo $file The file object
     *
     * @return array The configuration array
     */
    protected function doParse(SplFileInfo $file)
    {
        $options = [
            'class'        => null,
            'name'         => $file->getBasename(),
            'replace'      => [],
            'environments' => ['all'],
            'load-after'   => []
        ];

        $path = $file . '/config/autoload.ini';

        if (file_exists($path)) {
            $options['load-after'] = $this->normalize($this->parseIniFile($path));
        }

        return $options;
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
     * Check whether there is a "requires" section
     *
     * @param array $ini The autoload.ini configuration
     *
     * @return bool True if there is a "requires" section
     */
    protected function hasRequires(array $ini)
    {
        return isset($ini['requires']) && is_array($ini['requires']);
    }
}
