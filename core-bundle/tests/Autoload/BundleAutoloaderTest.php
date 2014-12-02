<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\BundleAutoloader;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the BundleAutoloader class.
 *
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class BundleAutoloaderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $bundleLoader = new BundleAutoloader($this->getRootDir(), 'test');

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\BundleAutoloader', $bundleLoader);
    }

    /**
     * Tests the load() method.
     */
    public function testLoad()
    {
        $bundleLoader = new BundleAutoloader($this->getRootDir() . '/app', 'test');

        $this->assertSame(
            [
                'ContaoCoreBundle' => 'Contao\CoreBundle\ContaoCoreBundle',
                'legacy-module'    => null,
                'with-requires'    => null,
                'without-requires' => null,
            ],
            $bundleLoader->load()
        );
    }
}
