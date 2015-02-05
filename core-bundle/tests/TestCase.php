<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

/**
 * Abstract TestCase class.
 *
 * @author Leo Feyer <https://contao.org>
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * Returns the path to the fixtures directory.
     *
     * @return string The root directory path
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $this->rootDir = __DIR__ . '/Fixtures';
        }

        return $this->rootDir;
    }
}
