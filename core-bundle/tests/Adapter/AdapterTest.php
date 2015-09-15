<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Adapter;

use Contao\CoreBundle\Adapter\Adapter;
use Contao\CoreBundle\Test\TestCase;


/**
 * Tests the Adapter class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AdapterTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $adapter = new Adapter('Dummy');

        $this->assertInstanceOf('Contao\\CoreBundle\\Adapter\\Adapter', $adapter);
    }
}
