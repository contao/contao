<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Cache\ContaoCacheClearer;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the CacheClearerClass class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CacheClearerClassTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $clearer = new ContaoCacheClearer(new Filesystem());

        $this->assertInstanceOf('Contao\\CoreBundle\\Cache\\ContaoCacheClearer', $clearer);
    }

    /**
     * Tests the clear() method.
     */
    public function testClear()
    {
        $fs       = new Filesystem();
        $cacheDir = $this->getCacheDir();

        $fs->mkdir("$cacheDir/contao");
        $this->assertFileExists("$cacheDir/contao");

        $clearer = new ContaoCacheClearer($fs);
        $clearer->clear($cacheDir);

        $this->assertFileNotExists("$cacheDir/contao");
    }
}
