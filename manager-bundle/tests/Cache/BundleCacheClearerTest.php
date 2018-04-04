<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Test\Cache;

use Contao\ManagerBundle\Cache\BundleCacheClearer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the BundleCacheClearer class.
 *
 * @author Kamil Kuzminski <https://github.com/qzminski>
 */
class BundleCacheClearerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $clearer = new BundleCacheClearer();

        $this->assertInstanceOf('Contao\ManagerBundle\Cache\BundleCacheClearer', $clearer);
    }

    /**
     * Tests the clear() method.
     */
    public function testClear()
    {
        $tmpdir = sys_get_temp_dir().'/'.uniqid('BundleCacheClearerTest_');

        $fs = new Filesystem();
        $fs->mkdir($tmpdir);
        $fs->touch($tmpdir.'/bundles.map');

        $this->assertFileExists($tmpdir.'/bundles.map');

        $clearer = new BundleCacheClearer($fs);
        $clearer->clear($tmpdir);

        $this->assertFileNotExists($tmpdir.'/bundles.map');

        $fs->remove($tmpdir);
    }
}
