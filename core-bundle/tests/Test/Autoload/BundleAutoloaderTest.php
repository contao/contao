<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\BundleAutoloader;

class BundleAutoloaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $bundleLoader = new BundleAutoloader('rootDir', 'env');

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\BundleAutoloader', $bundleLoader);
    }

    public function testLoad()
    {
        $bundleLoader = new BundleAutoloader(
            __DIR__ . '/../../fixtures/Autoload/BundleAutoloader/dummyRootDirName',
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
