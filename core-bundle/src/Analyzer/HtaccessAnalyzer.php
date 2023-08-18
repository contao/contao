<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Analyzer;

class HtaccessAnalyzer
{
    private readonly \SplFileInfo $file;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(\SplFileInfo $file)
    {
        if (!$file->isFile()) {
            throw new \InvalidArgumentException(sprintf('%s is not a file.', $file));
        }

        $this->file = $file;
    }

    public static function create(\SplFileInfo $file): self
    {
        return new self($file);
    }

    /**
     * Checks whether the .htaccess file grants access via HTTP.
     */
    public function grantsAccess(): bool
    {
        if (!$lines = file((string) $this->file)) {
            return false;
        }

        $content = array_filter($lines);

        foreach ($content as $line) {
            if ($this->hasRequireGranted($line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scans a line for an access definition.
     */
    private function hasRequireGranted(string $line): bool
    {
        if ($this->isComment($line)) {
            return false;
        }

        return false !== stripos($line, 'Allow from all') || false !== stripos($line, 'Require all granted');
    }

    /**
     * Checks whether a line is a comment.
     */
    private function isComment(string $line): bool
    {
        return 0 === strncmp('#', ltrim($line), 1);
    }
}
