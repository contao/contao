<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\HttpKernel;

use Contao\ManagerBundle\HttpKernel\ContaoCache;

/**
 * Tests the ContaoCache class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $cache = new ContaoCache($this->getMock('Symfony\Component\HttpKernel\Kernel', [], [], '', false), __DIR__);

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoCache', $cache);
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache', $cache);
    }
}
