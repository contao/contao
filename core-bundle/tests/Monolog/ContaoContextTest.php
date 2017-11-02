<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Tests\TestCase;

class ContaoContextTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $context = new ContaoContext('foo');

        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoContext', $context);
    }

    public function testSupportsReadingAndWritingValues(): void
    {
        $context = new ContaoContext('foo');

        $this->assertSame('foo', $context->getFunc());
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

        $this->assertSame(
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

    public function testFailsIfTheFunctionNameIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');

        new ContaoContext('');
    }
}
