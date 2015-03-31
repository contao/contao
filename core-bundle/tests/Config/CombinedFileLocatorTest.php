<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\CombinedFileLocator;
use Contao\CoreBundle\Config\FileLocator;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests the CombinedFileLocator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * TODO: add tests for bypassCache=true
 */
class CombinedFileLocatorTest extends TestCase
{
    /**
     * @var CombinedFileLocator
     */
    private $locator;

    /**
     * Creates the FileLocator object.
     */
    protected function setUp()
    {
        $this->locator = new CombinedFileLocator(
            $this->getCacheDir() . '/contao',
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
        $this->assertInstanceOf('Contao\CoreBundle\Config\CombinedFileLocator', $this->locator);
    }

    /**
     * Tests locating all resources with a cache hit.
     */
    public function testCacheHit()
    {
        $files = $this->locator->locate('config/autoload.php');

        $this->assertCount(1, $files);
        $this->assertContains($this->getCacheDir() . '/contao/config/autoload.php', $files);
    }

    /**
     * Tests locating the first resource with a cache hit.
     */
    public function testCacheHitFirst()
    {
        $file = $this->locator->locate('config/autoload.php', null, true);

        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $file);
    }

    /**
     * Tests locating all resources without a cache hit.
     */
    public function testCacheMiss()
    {
        $files = $this->locator->locate('config/config.php');

        $this->assertCount(2, $files);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $files);
        $this->assertContains($this->getRootDir() . '/system/modules/foobar/config/config.php', $files);
    }

    /**
     * Tests locating the first resource without a cache hit.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testCacheMissFirst()
    {
        $this->locator->locate('config/config.php', null, true);
    }
}
