<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Tests\TestCase;

class ContaoContextTest extends TestCase
{
    public function testSupportsReadingAndWritingValues(): void
    {
        $context = new ContaoContext('foo');

        $this->assertSame('foo', $context->getFunc());
        $this->assertNull($context->getAction());
        $this->assertNull($context->getUsername());
        $this->assertNull($context->getIp());
        $this->assertNull($context->getBrowser());
        $this->assertNull($context->getSource());
        $this->assertNull($context->getUri());
        $this->assertNull($context->getPageId());

        $context->setAction('action');
        $context->setUsername('username');
        $context->setIp('1.2.3.4');
        $context->setBrowser('Mozilla');
        $context->setSource('Foo::bar()');
        $context->setUri('https://www.contao.org');
        $context->setPageId(42);

        $json = json_encode([
            'func' => 'foo',
            'action' => 'action',
            'username' => 'username',
            'browser' => 'Mozilla',
            'uri' => 'https://www.contao.org',
            'pageId' => 42,
        ]);

        $this->assertSame($json, (string) $context);
    }

    public function testFailsIfTheFunctionNameIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');

        new ContaoContext('');
    }
}
