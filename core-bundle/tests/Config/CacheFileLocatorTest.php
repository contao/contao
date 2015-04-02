<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\CacheFileLocator;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Tests the CacheFileLocatorTest class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CacheFileLocatorTest extends TestCase
{
    /**
     * @var FileLocatorInterface
     */
    private $locator;

    /**
     * Creates the FileLocator object.
     */
    protected function setUp()
    {
        $this->locator = new CacheFileLocator($this->getCacheDir() . '/contao');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\CacheFileLocator', $this->locator);
    }

    /**
     * Tests locating a single file.
     */
    public function testLocateSingleFile()
    {
        $file = $this->locator->locate('config/autoload.php');

        $this->assertInternalType('string', $file);
        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $file);
    }

    /**
     * Tests locating multiple files.
     */
    public function testLocateMultipleFiles()
    {
        $files = $this->locator->locate('config/autoload.php', null, false);

        $this->assertInternalType('array', $files);
        $this->assertCount(1, $files);
        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $files[0]);
    }

    /**
     * Tests locating a folder.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLocateFolder()
    {
        $this->locator->locate('config');
    }

    /**
     * Tests locating an empty file
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLocateEmpty()
    {
        $this->locator->locate('');
    }

    /**
     * Tests locating a non-existent file
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLocateNonExistent()
    {
        $this->locator->locate('config/foo.php');
    }
}
