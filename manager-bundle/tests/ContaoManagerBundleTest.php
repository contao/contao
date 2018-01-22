<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests;

use Contao\ManagerBundle\ContaoManagerBundle;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ContaoManagerBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoManagerBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManagerBundle', new ContaoManagerBundle());
    }
}
