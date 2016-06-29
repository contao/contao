<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Autoload;

use Contao\ManagerBundle\Autoload\BundleAutoloader;

class BundleAutoloaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $bundleLoader = new BundleAutoloader('rootDir', 'env');

        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\BundleAutoloader', $bundleLoader);
    }

    public function testLoad()
    {
        $bundleLoader = new BundleAutoloader(
            __DIR__ . '/../Fixtures/Autoload/BundleAutoloader/dummyRootDirName',
            'all'
        );

        $this->assertSame(
            [
                'ContaoCoreBundle'  => 'Contao\CoreBundle\ContaoCoreBundle',
                'legacy-module'     => null
            ],
            $bundleLoader->load()
        );
    }
}
