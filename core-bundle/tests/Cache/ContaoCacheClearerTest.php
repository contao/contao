<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Cache;

use Contao\CoreBundle\Cache\ContaoCacheClearer;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ContaoCacheClearerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $clearer = new ContaoCacheClearer(new Filesystem());

        $this->assertInstanceOf('Contao\CoreBundle\Cache\ContaoCacheClearer', $clearer);
    }

    public function testRemovesTheCacheFolder(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getTempDir().'/contao/config');

        $this->assertFileExists($this->getTempDir().'/contao/config');

        $clearer = new ContaoCacheClearer($fs);
        $clearer->clear($this->getTempDir());

        $this->assertFileNotExists($this->getTempDir().'/contao/config');
    }
}
