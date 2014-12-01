<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\HttpKernel\Bundle;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;

/**
 * Tests the ContaoModuleBundle class.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoModuleBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContaoModuleBundle
     */
    protected $bundle;

    /**
     * Creates a new Contao module bundle.
     */
    protected function setUp()
    {
        $this->bundle = new ContaoModuleBundle('legacy-module', __DIR__);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle', $this->bundle);
    }

    /**
     * Tests the getPublicFolders() method.
     */
    public function testGetPublicFolders()
    {
        $this->assertEquals(
            [
                dirname(__DIR__) . '/system/modules/legacy-module/assets',
            ],
            $this->bundle->getPublicFolders()
        );
    }

    /**
     * Tests the getContaoResourcesPath() method.
     */
    public function testGetContaoResourcesPath()
    {
        $this->assertEquals(
            dirname(__DIR__) . '/system/modules/legacy-module',
            $this->bundle->getContaoResourcesPath()
        );
    }

    /**
     * Tests the getPath() method.
     */
    public function testGetPath()
    {
        $this->assertEquals(
            dirname(__DIR__) . '/system/modules/legacy-module',
            $this->bundle->getPath()
        );
    }
}
