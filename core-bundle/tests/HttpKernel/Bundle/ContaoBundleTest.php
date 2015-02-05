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
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ContaoBundle class.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoBundleTest extends TestCase
{
    /**
     * Tests the getPublicFolders() method.
     */
    public function testGetPublicFolders()
    {
        $bundle = new ContaoCoreBundle();

        $this->assertEmpty($bundle->getPublicFolders());
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
