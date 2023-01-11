<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Cache;

use Contao\ManagerBundle\Cache\BundleCacheClearer;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Filesystem\Filesystem;

class BundleCacheClearerTest extends ContaoTestCase
{
    public function testClear(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getTempDir());
        $fs->touch($this->getTempDir().'/bundles.map');

        $this->assertFileExists($this->getTempDir().'/bundles.map');

        $clearer = new BundleCacheClearer($fs);
        $clearer->clear($this->getTempDir());

        $this->assertFileDoesNotExist($this->getTempDir().'/bundles.map');
    }
}
