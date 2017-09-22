<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Cache;

use Contao\ManagerBundle\Cache\BundleCacheClearer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class BundleCacheClearerTest extends TestCase
{
    public function testInstantiation(): void
    {
        $clearer = new BundleCacheClearer();

        $this->assertInstanceOf('Contao\ManagerBundle\Cache\BundleCacheClearer', $clearer);
    }

    public function testClear(): void
    {
        $tmpdir = sys_get_temp_dir().'/'.uniqid('BundleCacheClearerTest_', false);

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
