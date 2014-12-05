<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Analyzes an .htaccess file.
 *
 * @author Leo Feyer <https://contao.org>
 */
class HtaccessAnalyzer
{
    /**
     * @var SplFileInfo
     */
    protected $file;

    /**
     * Stores the file object.
     *
     * @param SplFileInfo $file The file object
     *
     * @throws \InvalidArgumentException If $file is not a file
     */
    public function __construct(SplFileInfo $file)
    {
        if (!$file->isFile()) {
            throw new \InvalidArgumentException("$file is not a file");
        }

        $this->file = $file;
    }

    /**
     * Checks whether the .htaccess file grants access via HTTP.
     *
     * @return bool True if the .htaccess file grants access via HTTP
     */
    public function grantsAcces()
    {
        $content = array_filter(file($this->file));

        foreach ($content as $line) {
            if ($this->hasRequireGranted($line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scans a line for an access definition.
     *
     * @param string $line The line
     *
     * @return bool True if the line has an access definition
     */
    protected function hasRequireGranted($line)
    {
        // Ignore comments
        if (0 === strncmp('#', trim($line), 1)) {
            return false;
        }

        if (false !== stripos($line, 'Allow from all')) {
            return true;
        }

        if (false !== stripos($line, 'Require all granted')) {
            return true;
        }

        return false;
    }
}
