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
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ContaoCacheTest extends ContaoTestCase
{
    public function testInstantiation(): void
    {
        $cache = new ContaoCache($this->createMock(KernelInterface::class), $this->getTempDir());

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoCache', $cache);
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache', $cache);
    }
}
