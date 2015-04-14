<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Analyzer;

/**
 * Analyzes an .htaccess file.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class HtaccessAnalyzer
{
    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * Stores the file object.
     *
     * @param \SplFileInfo $file The file object
     *
     * @throws \InvalidArgumentException If $file is not a file
     */
    public function __construct(\SplFileInfo $file)
    {
        if (!$file->isFile()) {
            throw new \InvalidArgumentException("$file is not a file.");
        }

        $this->file = $file;
    }

    /**
     * Checks whether the .htaccess file grants access via HTTP.
     *
     * @return bool True if the .htaccess file grants access via HTTP
     */
    public function grantsAccess()
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
     * @param string $line The current line
     *
     * @return bool True if the line has an access definition
     */
    private function hasRequireGranted($line)
    {
        if ($this->isComment($line)) {
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

    /**
     * Checks whether a line is a comment.
     *
     * @param string $line The current line
     *
     * @return bool True if the line is a comment
     */
    private function isComment($line)
    {
        return 0 === strncmp('#', ltrim($line), 1);
    }
}
