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

    /**
     * Tests the createInstance method.
     */
    public function testCreateInstance()
    {
        $factory = new AdapterFactory();
        $class = 'Contao\\CoreBundle\\Test\\Fixtures\\Adapter\\LegacyClass';

        $this->assertInstanceOf($class, $factory->createInstance($class));
    }

    /**
     * Tests the createInstance method for a singleton class.
     */
    public function testCreateInstanceSingelton()
    {
        $factory = new AdapterFactory();
        $class = 'Contao\\CoreBundle\\Test\\Fixtures\\Adapter\\LegacySingletonClass';

        $this->assertInstanceOf($class, $factory->createInstance($class));
    }

    /**
     * Tests the getAdapter method.
     */
    public function testGetAdapter()
    {
        $factory = new AdapterFactory();
        $class = 'Contao\\CoreBundle\\Test\\Fixtures\\Adapter\\LegacyClass';

        $this->assertInstanceOf('Contao\\CoreBundle\\Adapter\\Adapter', $factory->getAdapter($class));
    }
}
