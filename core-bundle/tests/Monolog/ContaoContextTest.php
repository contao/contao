<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ContaoContextTest class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoContextTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoContext', new ContaoContext('foo'));
    }

    /**
     * Tests the setter and getter methods.
     */
    public function testSettersAndGetters()
    {
        $context = new ContaoContext('foo');

        $this->assertEquals('foo', $context->getFunc());
        $this->assertNull($context->getAction());
        $this->assertNull($context->getUsername());
        $this->assertNull($context->getIp());
        $this->assertNull($context->getBrowser());
        $this->assertNull($context->getSource());

        $context->setAction('action');
        $context->setUsername('username');
        $context->setIp('1.2.3.4');
        $context->setBrowser('Mozilla');
        $context->setSource('Foo::bar()');

        $this->assertEquals(
            json_encode([
                'func' => 'foo',
                'action' => 'action',
                'username' => 'username',
                'ip' => '1.2.3.4',
                'browser' => 'Mozilla',
            ]),
            (string) $context
        );
    }

    /**
     * Tests passing an empty function name.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyFunctionName()
    {
        new ContaoContext('');
    }
}
