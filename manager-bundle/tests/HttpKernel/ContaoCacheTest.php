<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use Contao\ManagerBundle\HttpKernel\ContaoCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the ContaoCache class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCacheTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $tmpdir = sys_get_temp_dir().'/'.uniqid('BundleCacheClearerTest_', true);

        $fs = new Filesystem();
        $fs->mkdir($tmpdir);

        $cache = new ContaoCache($this->createMock(KernelInterface::class), $tmpdir);

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoCache', $cache);
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache', $cache);

        $fs->remove($tmpdir);
    }
}
