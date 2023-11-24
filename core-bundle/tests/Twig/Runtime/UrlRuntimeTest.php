<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\UrlRuntime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UrlRuntimeTest extends TestCase
{
    public function testPrefixesRelativeUrls(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $runtime = new UrlRuntime($requestStack);

        $this->assertSame('/en/content-elements.html', $runtime->prefixUrl('en/content-elements.html'));
    }

    public function testAddsTheBasePathWhenPrefixingUrls(): void
    {
        $request = Request::create('https://localhost/managed-edition/public/contao/preview');
        $request->server->set('SCRIPT_NAME', '/managed-edition/public/index.php');
        $request->server->set('SCRIPT_FILENAME', '/managed-edition/public/index.php');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $runtime = new UrlRuntime($requestStack);

        $this->assertSame('/managed-edition/public/en/content-elements.html', $runtime->prefixUrl('en/content-elements.html'));
    }

    public function testAddsASlashIfThereIsNoRequest(): void
    {
        $runtime = new UrlRuntime(new RequestStack());

        $this->assertSame('/en/content-elements.html', $runtime->prefixUrl('en/content-elements.html'));
    }

    public function testDoesNotPrefixNonRelativeUrls(): void
    {
        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $runtime = new UrlRuntime($requestStack);

        $this->assertSame('/en/content-elements.html', $runtime->prefixUrl('/en/content-elements.html'));
        $this->assertSame('https://localhost', $runtime->prefixUrl('https://localhost'));
        $this->assertSame('#foo', $runtime->prefixUrl('#foo'));
        $this->assertSame('{{link::52}}', $runtime->prefixUrl('{{link::52}}'));
    }
}
