<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Test;

use Contao\NewsBundle\ContaoNewsBundle;

/**
 * Tests the ContaoNewsBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoNewsBundleTest extends \PHPUnit_Framework_TestCase
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
