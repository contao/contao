<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
    public function testCanBeInstantiated()
    {
        $bundle = new ContaoNewsBundle();

        $this->assertInstanceOf('Contao\NewsBundle\ContaoNewsBundle', $bundle);
    }
}
