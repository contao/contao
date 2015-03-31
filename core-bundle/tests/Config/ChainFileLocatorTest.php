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
use Contao\CoreBundle\Config\CombinedFileLocator;
use Contao\CoreBundle\Config\FileLocator;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests the ChainFileLocator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ChainFileLocatorTest extends TestCase
{
    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * Creates the FileLocator object.
     */
    protected function setUp()
    {
        $this->locator = new ChainFileLocator();
        $this->locator->addLocator(new CombinedFileLocator($this->getCacheDir() . '/contao'));

        $this->locator->addLocator(
            new FileLocator([
                'TestBundle' => $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
                'foobar'     => $this->getRootDir() . '/system/modules/foobar'
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
     * Tests locating all resources.
     */
    public function testLocateAll()
    {
        $files = array_values($this->locator->locate('config/autoload.php'));

        $this->assertCount(3, $files);
        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $files[0]);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/autoload.php', $files[1]);
        $this->assertEquals($this->getRootDir() . '/system/modules/foobar/config/autoload.php', $files[2]);
    }

    /**
     * Tests locating the first resource.
     */
    public function testLocateFirst()
    {
        $file = $this->locator->locate('config/autoload.php', null, true);

        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $file);
    }

    /**
     * Tests locating the first resource with a non-existing file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLocateFirstNotFound()
    {
        $this->locator->locate('config/foo.php', null, true);
    }
}
