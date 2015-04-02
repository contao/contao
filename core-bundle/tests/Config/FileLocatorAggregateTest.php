<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\FileLocatorAggregate;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Tests the FileLocatorAggregate class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class FileLocatorAggregateTest extends TestCase
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
        $this->locator = new FileLocatorAggregate([
            new FileLocator([
                $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
                $this->getRootDir() . '/system/modules/foobar'
            ])
        ]);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\FileLocatorAggregate', $this->locator);
    }

    /**
     * Tests locating a single file.
     */
    public function testLocateSingleFile()
    {
        $files = $this->locator->locate('config/autoload.php');

        $this->assertInternalType('array', $files);
        $this->assertCount(1, $files);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/autoload.php', $files[0]);
    }

    /**
     * Tests locating multiple files.
     */
    public function testLocateMultipleFiles()
    {
        $files = $this->locator->locate('config/autoload.php', null, false);

        $this->assertInternalType('array', $files);
        $this->assertCount(2, $files);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/autoload.php', $files[0]);
        $this->assertEquals($this->getRootDir() . '/system/modules/foobar/config/autoload.php', $files[1]);
    }

    /**
     * Tests locating an invalid file.
     */
    public function testLocateInvalidFile()
    {
        $files = $this->locator->locate('config/foo.php');

        $this->assertInternalType('array', $files);
        $this->assertEmpty($files);
    }
}
