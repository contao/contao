<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Adapter;

use Contao\CoreBundle\Adapter\AdapterFactory;
use Contao\CoreBundle\Test\TestCase;


/**
 * Tests the AdapterFactory class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AdapterFactoryTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $factory = new AdapterFactory();

        $this->assertInstanceOf('Contao\\CoreBundle\\Adapter\\AdapterFactory', $factory);
    }
}
