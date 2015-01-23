<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\HttpKernel\Bundle;

use Contao\CoreBundle\ContaoCoreBundle;

/**
 * Tests the ContaoBundle class.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the getPublicFolders() method.
     */
    public function testGetPublicFolders()
    {
        $bundle = new ContaoCoreBundle();

        $this->assertEquals(
            [],
            $bundle->getPublicFolders()
        );
    }

    /**
     * Tests the getContaoResourcesPath() method.
     */
    public function testGetContaoResourcesPath()
    {
        $bundle = new ContaoCoreBundle();

        $this->assertEquals(
            $bundle->getPath() . '/../contao',
            $bundle->getContaoResourcesPath()
        );
    }
}
