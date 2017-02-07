<?php

namespace Contao\ManagerBundle\Test\Cache;

use Contao\CoreBundle\Test\TestCase;
use Contao\ManagerBundle\Cache\BundleCacheClearer;
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
        $clearer = new BundleCacheClearer(new Filesystem());

        $this->assertInstanceOf('Contao\ManagerBundle\Cache\BundleCacheClearer', $clearer);
    }

    /**
     * Tests the clear() method.
     */
    public function testClear()
    {
        $fs = new Filesystem();
        $cacheDir = $this->getCacheDir();

        $fs->touch("$cacheDir/bundles.map");
        $this->assertFileExists("$cacheDir/bundles.map");

        $clearer = new BundleCacheClearer($fs);
        $clearer->clear($cacheDir);

        $this->assertFileNotExists("$cacheDir/bundles.map");
    }
}
