<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\BasePathPrefixer;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BasePathPrefixerTest extends TestCase
{
    public function testPrefixesTheBasePathToRelativeLinks(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://localhost'));

        $basePathPrefixer = new BasePathPrefixer($requestStack);

        $this->assertSame('/', $basePathPrefixer->prefix(''));
        $this->assertSame('/foo.html', $basePathPrefixer->prefix('foo.html'));
        $this->assertSame('https://localhost', $basePathPrefixer->prefix('https://localhost'));
        $this->assertSame('/bar', $basePathPrefixer->prefix('/bar'));
        $this->assertSame('#foo', $basePathPrefixer->prefix('#foo'));
        $this->assertSame('{{link::5}}', $basePathPrefixer->prefix('{{link::5}}'));
        $this->assertSame('{{env::base_path}}/foo.html', $basePathPrefixer->prefix('{{env::base_path}}/foo.html'));
        $this->assertSame('tel:1234', $basePathPrefixer->prefix('tel:1234'));
    }
}
