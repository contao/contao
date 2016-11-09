<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Framework\Adapter;

use Contao\CoreBundle\Framework\Adapter;

/**
 * Tests the Adapter class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $adapter = new Adapter('Dummy');

        $this->assertInstanceOf('Contao\CoreBundle\Framework\Adapter', $adapter);
    }

    /**
     * Tests the __call method.
     */
    public function testMagicCall()
    {
        $adapter = new Adapter('Contao\CoreBundle\Test\Fixtures\Adapter\LegacyClass');

        $this->assertEquals(['staticMethod', 1, 2], $adapter->staticMethod(1, 2));
    }

    /**
     * Tests the __call method of a non-existent function.
     *
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testMagicCallMissingMethod()
    {
        $adapter = new Adapter('Contao\CoreBundle\Test\Fixtures\Adapter\LegacyClass');

        $adapter->missingMethod();
    }
}
