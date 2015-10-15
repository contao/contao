<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Adapter;

use Contao\CoreBundle\Framework\Adapter\GeneralAdapter;
use Contao\CoreBundle\Test\TestCase;


/**
 * Tests the GeneralAdapter class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class GeneralAdapterTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $adapter = new GeneralAdapter('Dummy');

        $this->assertInstanceOf('Contao\\CoreBundle\\Framework\\Adapter\\GeneralAdapter', $adapter);
        $this->assertInstanceOf('Contao\\CoreBundle\\Framework\\Adapter\\AdapterInterface', $adapter);
    }

    /**
     * Tests the __call method.
     */
    public function testMagicCall()
    {
        $adapter = new GeneralAdapter('Contao\\CoreBundle\\Test\\Fixtures\\Adapter\\LegacyClass');

        $this->assertEquals(['staticMethod', 1, 2], $adapter->staticMethod(1, 2));
    }

    /**
     * Tests the __call method of a non-existent function.
     */
    public function testMagicCallMissingMethod()
    {
        $adapter = new GeneralAdapter('Contao\\CoreBundle\\Test\\Fixtures\\Adapter\\LegacyClass');

        $this->setExpectedException('PHPUnit_Framework_Error');
        $adapter->missingMethod();
    }
}
