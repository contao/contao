<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests;

use Contao\NewsBundle\ContaoNewsBundle;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ContaoNewsBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoNewsBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $bundle = new ContaoNewsBundle();

        $this->assertInstanceOf('Contao\NewsBundle\ContaoNewsBundle', $bundle);
    }
}
