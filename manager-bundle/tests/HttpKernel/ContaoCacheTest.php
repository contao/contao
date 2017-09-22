<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use Contao\ManagerBundle\HttpKernel\ContaoCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class ContaoCacheTest extends TestCase
{
    public function testInstantiation(): void
    {
        $tmpdir = sys_get_temp_dir().'/'.uniqid('BundleCacheClearerTest_', false);

        $fs = new Filesystem();
        $fs->mkdir($tmpdir);

        $cache = new ContaoCache($this->createMock(KernelInterface::class), $tmpdir);

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoCache', $cache);
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache', $cache);

        $fs->remove($tmpdir);
    }
}
