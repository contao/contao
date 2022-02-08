<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cache;

use Contao\CoreBundle\Cache\ContaoCacheClearer;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ContaoCacheClearerTest extends TestCase
{
    public function testRemovesTheCacheFolder(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getTempDir().'/contao/config');

        $this->assertFileExists($this->getTempDir().'/contao/config');

        $clearer = new ContaoCacheClearer($fs);
        $clearer->clear($this->getTempDir());

        $this->assertFileDoesNotExist($this->getTempDir().'/contao/config');
    }
}
