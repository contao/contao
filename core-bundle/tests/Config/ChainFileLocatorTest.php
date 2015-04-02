<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\ChainFileLocator;
use Contao\CoreBundle\Config\StrictFileLocator;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Tests the ChainFileLocator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ChainFileLocatorTest extends TestCase
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
        $this->locator = new ChainFileLocator();
        $this->locator->addLocator(new StrictFileLocator($this->getCacheDir() . '/contao'));

        $this->locator->addLocator(
            new FileLocator([
                $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
                $this->getRootDir() . '/system/modules/foobar'
            ])
        );
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\ChainFileLocator', $this->locator);
    }

    /**
     * Tests locating a cached file.
     */
    public function testLocateCachedFile()
    {
        $file = $this->locator->locate('config/autoload.php');

        $this->assertInternalType('string', $file);
        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $file);
    }

    /**
     * Tests locating a non-cached file.
     */
    public function testLocateAll()
    {
        $files = $this->locator->locate('config/config.php', null, false);

        $this->assertInternalType('array', $files);
        $this->assertCount(2, $files);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $files[0]);
        $this->assertEquals($this->getRootDir() . '/system/modules/foobar/config/config.php', $files[1]);
    }

    /**
     * Tests locating an invalid resource
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLocateInvalid()
    {
        $this->locator->locate('config/foo.php');
    }
}
