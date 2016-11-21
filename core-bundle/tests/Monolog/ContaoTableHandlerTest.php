<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Monolog;

use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ContaoTableHandler class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoTableHandlerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableHandler', new ContaoTableHandler());
    }

    /**
     * Tests setting and retrieving the DBAL service name.
     */
    public function testSetAndGetDbalServiceName()
    {
        $handler = new ContaoTableHandler();

        $this->assertEquals('doctrine.dbal.default_connection', $handler->getDbalServiceName());

        $handler->setDbalServiceName('foobar');

        $this->assertEquals('foobar', $handler->getDbalServiceName());
    }
}
